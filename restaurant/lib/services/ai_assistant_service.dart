import 'api_service.dart';

/// Restaurant-facing AI growth assistant.
///
/// Contract (see backend spec):
///   GET  /api/ai/status                       -> { data: { enabled, service_enabled } }
///   POST /api/restaurant/assistant/message    body { message, conversation_id? }
///        -> { data: { conversation_id, reply, plan?: [{title, steps:[]}], suggestions?: [] } }
///   GET  /api/restaurant/assistant/history    -> { data: { conversation_id, messages: [{role, text, plan?}] } }
class AiAssistantService {
  AiAssistantService._();
  static final AiAssistantService instance = AiAssistantService._();

  final ApiService _api = ApiService();

  /// Whether the restaurant growth assistant is available.
  ///
  /// Reads `GET /api/restaurant/assistant/status` (contract: reflects the admin
  /// AI Control Center — OpenAI/Gemini key configured + kill-switch off). If that
  /// endpoint isn't deployed yet we stay optimistic and let [send] surface the
  /// real error, rather than falsely showing "offline".
  Future<bool> isEnabled() async {
    try {
      final res = await _api.get('/restaurant/assistant/status');
      final data = res is Map ? res['data'] : null;
      if (data is Map && data.containsKey('enabled')) {
        return data['enabled'] == true;
      }
    } catch (_) {}
    return true;
  }

  Future<AiAssistantReply> send(String message, {String? conversationId}) async {
    final res = await _api.post(
      '/restaurant/assistant/message',
      data: {
        'message': message,
        if (conversationId != null) 'conversation_id': conversationId,
      },
    );
    final data = (res is Map ? res['data'] : null);
    if (data is! Map) {
      throw Exception('Unexpected assistant response.');
    }
    return AiAssistantReply.fromJson(Map<String, dynamic>.from(data));
  }

  /// Confirm (`confirm: true`) or dismiss (`confirm: false`) an action the
  /// assistant proposed. Backend re-validates + runs it, scoped to this
  /// restaurant, and returns the outcome.
  Future<AiActionResult> runAction(int actionId, {required bool confirm}) async {
    final res = await _api.post(
      '/restaurant/assistant/action',
      data: {'action_id': actionId, 'confirm': confirm},
    );
    final data = (res is Map ? res['data'] : null);
    if (data is! Map) {
      throw Exception('Unexpected response.');
    }
    return AiActionResult.fromJson(Map<String, dynamic>.from(data));
  }

  Future<List<AiAssistantMessage>> history({String? conversationId}) async {
    try {
      final res = await _api.get(
        '/restaurant/assistant/history',
        queryParams: conversationId == null
            ? null
            : {'conversation_id': conversationId},
      );
      final data = res is Map ? res['data'] : null;
      final raw = data is Map ? data['messages'] : null;
      if (raw is List) {
        return raw
            .whereType<Map>()
            .map((m) => AiAssistantMessage.fromJson(
                Map<String, dynamic>.from(m)))
            .toList();
      }
    } catch (_) {}
    return const [];
  }

  /// Proactive growth proposals for the dashboard popup — coupons / dish price
  /// changes the AI thinks are worth doing, based on the last 30 days. Each is a
  /// proposed [AiAction]; run or dismiss it with [runAction]. Returns an empty
  /// list when the AI is off, nothing is worth proposing, or the call fails.
  Future<AiProposals> proposals() async {
    try {
      final res = await _api.get('/restaurant/assistant/proposals');
      final data = res is Map ? res['data'] : null;
      if (data is Map) {
        final raw = data['proposals'];
        final actions = raw is List
            ? raw
                .whereType<Map>()
                .map((a) => AiAction.fromJson(Map<String, dynamic>.from(a)))
                .where((a) => a.id > 0)
                .toList()
            : <AiAction>[];
        return AiProposals(
          actions: actions,
          reply: data['reply']?.toString() ?? '',
        );
      }
    } catch (_) {}
    return const AiProposals(actions: [], reply: '');
  }

  /// Past conversations for this restaurant, newest first.
  Future<List<AiConversation>> conversations() async {
    try {
      final res = await _api.get('/restaurant/assistant/conversations');
      final data = res is Map ? res['data'] : null;
      final raw = data is Map ? data['conversations'] : null;
      if (raw is List) {
        return raw
            .whereType<Map>()
            .map((c) => AiConversation.fromJson(Map<String, dynamic>.from(c)))
            .toList();
      }
    } catch (_) {}
    return const [];
  }
}

class AiProposals {
  const AiProposals({required this.actions, required this.reply});
  final List<AiAction> actions;
  final String reply;

  bool get isEmpty => actions.isEmpty;
}

class AiConversation {
  AiConversation({
    required this.id,
    required this.title,
    required this.preview,
    required this.messageCount,
    this.updatedAt,
  });

  final String id;
  final String title;
  final String preview;
  final int messageCount;
  final DateTime? updatedAt;

  factory AiConversation.fromJson(Map<String, dynamic> j) => AiConversation(
        id: j['conversation_id']?.toString() ?? '',
        title: (j['title']?.toString() ?? '').trim().isEmpty
            ? 'Conversation'
            : j['title'].toString(),
        preview: j['preview']?.toString() ?? '',
        messageCount: int.tryParse('${j['message_count'] ?? 0}') ?? 0,
        updatedAt: DateTime.tryParse('${j['updated_at'] ?? ''}'),
      );
}

class AiPlan {
  AiPlan({required this.title, required this.steps});
  final String title;
  final List<String> steps;

  factory AiPlan.fromJson(Map<String, dynamic> j) => AiPlan(
        title: j['title']?.toString() ?? 'Plan',
        steps: (j['steps'] as List? ?? const [])
            .map((s) => s.toString())
            .toList(),
      );
}

/// One concrete task the assistant wants to run (create a coupon, change a
/// price, pause an ad…). Shown as a confirm card; nothing happens until the
/// owner taps approve.
class AiAction {
  AiAction({
    required this.id,
    required this.tool,
    required this.summary,
    this.risk = 'low',
    this.status = 'proposed',
  });

  final int id;
  final String tool;
  final String summary;
  final String risk;
  String status; // proposed | executed | failed | cancelled

  factory AiAction.fromJson(Map<String, dynamic> j) => AiAction(
        id: int.tryParse('${j['id']}') ?? 0,
        tool: j['tool']?.toString() ?? '',
        summary: j['summary']?.toString() ?? (j['tool']?.toString() ?? 'Action'),
        risk: j['risk']?.toString() ?? 'low',
        status: j['status']?.toString() ?? 'proposed',
      );
}

class AiActionResult {
  AiActionResult({
    required this.actionId,
    required this.status,
    required this.ok,
    required this.message,
  });

  final int actionId;
  final String status;
  final bool ok;
  final String message;

  factory AiActionResult.fromJson(Map<String, dynamic> j) => AiActionResult(
        actionId: int.tryParse('${j['action_id']}') ?? 0,
        status: j['status']?.toString() ?? 'failed',
        ok: j['ok'] == true,
        message: j['message']?.toString() ?? '',
      );
}

class AiAssistantReply {
  AiAssistantReply({
    required this.conversationId,
    required this.reply,
    this.plans = const [],
    this.suggestions = const [],
    this.actions = const [],
  });

  final String? conversationId;
  final String reply;
  final List<AiPlan> plans;
  final List<String> suggestions;
  final List<AiAction> actions;

  factory AiAssistantReply.fromJson(Map<String, dynamic> j) {
    final rawPlan = j['plan'] ?? j['plans'];
    return AiAssistantReply(
      conversationId: j['conversation_id']?.toString(),
      reply: j['reply']?.toString() ?? j['message']?.toString() ?? '',
      plans: rawPlan is List
          ? rawPlan
              .whereType<Map>()
              .map((p) => AiPlan.fromJson(Map<String, dynamic>.from(p)))
              .toList()
          : const [],
      suggestions: (j['suggestions'] as List? ?? const [])
          .map((s) => s.toString())
          .toList(),
      actions: (j['actions'] as List? ?? const [])
          .whereType<Map>()
          .map((a) => AiAction.fromJson(Map<String, dynamic>.from(a)))
          .toList(),
    );
  }
}

class AiAssistantMessage {
  AiAssistantMessage({
    required this.fromUser,
    required this.text,
    this.plans = const [],
  });

  final bool fromUser;
  final String text;
  final List<AiPlan> plans;

  factory AiAssistantMessage.fromJson(Map<String, dynamic> j) {
    final role = j['role']?.toString() ?? (j['from_user'] == true ? 'user' : 'assistant');
    final rawPlan = j['plan'] ?? j['plans'];
    return AiAssistantMessage(
      fromUser: role == 'user',
      text: j['text']?.toString() ?? j['message']?.toString() ?? '',
      plans: rawPlan is List
          ? rawPlan
              .whereType<Map>()
              .map((p) => AiPlan.fromJson(Map<String, dynamic>.from(p)))
              .toList()
          : const [],
    );
  }
}
