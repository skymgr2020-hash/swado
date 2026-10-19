class Session {
  const Session({
    required this.token,
    required this.baseUrl,
    required this.name,
    required this.role,
    this.id,
    this.email,
    this.phone,
    this.profileImage,
    this.roles = const [],
    this.permissions = const [],
    this.settings = const {},
  });

  final String token;
  final String baseUrl;
  final String name;
  final String role;
  final int? id;
  final String? email;
  final String? phone;
  final String? profileImage;
  final List<String> roles;
  final List<String> permissions;
  final Map<String, dynamic> settings;

  bool get canUseOnboardApp => const {
    'super_admin',
    'admin',
    'marketing_manager',
    'fleet_manager',
  }.contains(role);

  bool get canManageRestaurants =>
      role == 'super_admin' || role == 'admin' || role == 'marketing_manager';

  bool get canManageDrivers =>
      role == 'super_admin' || role == 'admin' || role == 'fleet_manager';

  Session copyWith({
    String? token,
    String? baseUrl,
    String? name,
    String? role,
    int? id,
    String? email,
    String? phone,
    String? profileImage,
    List<String>? roles,
    List<String>? permissions,
    Map<String, dynamic>? settings,
  }) {
    return Session(
      token: token ?? this.token,
      baseUrl: baseUrl ?? this.baseUrl,
      name: name ?? this.name,
      role: role ?? this.role,
      id: id ?? this.id,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      profileImage: profileImage ?? this.profileImage,
      roles: roles ?? this.roles,
      permissions: permissions ?? this.permissions,
      settings: settings ?? this.settings,
    );
  }

  factory Session.fromAuthPayload({
    required String token,
    required String baseUrl,
    required Map<String, dynamic> user,
    String? requestedRole,
  }) {
    final roles = _stringList(user['roles']);
    final payloadRole = user['role']?.toString().toLowerCase();
    final role = payloadRole?.isNotEmpty == true
        ? payloadRole!
        : requestedRole?.toLowerCase() ??
              (roles.isNotEmpty ? roles.first : 'admin');

    return Session(
      token: token,
      baseUrl: baseUrl,
      id: _intOrNull(user['id']),
      name: user['name']?.toString() ?? 'Operator',
      email: user['email']?.toString(),
      phone: user['phone']?.toString(),
      role: role,
      profileImage: (user['profile_image'] ?? user['profile_photo_url'])
          ?.toString(),
      roles: roles,
      permissions: _stringList(user['permissions']),
      settings: _map(user['settings']),
    );
  }

  factory Session.fromJson(Map<String, dynamic> json) {
    return Session(
      token: json['token']?.toString() ?? '',
      baseUrl: json['baseUrl']?.toString() ?? '',
      id: _intOrNull(json['id']),
      name: json['name']?.toString() ?? 'Operator',
      email: json['email']?.toString(),
      phone: json['phone']?.toString(),
      role: json['role']?.toString() ?? 'admin',
      profileImage: json['profileImage']?.toString(),
      roles: _stringList(json['roles']),
      permissions: _stringList(json['permissions']),
      settings: _map(json['settings']),
    );
  }

  Map<String, dynamic> toJson() => {
    'token': token,
    'baseUrl': baseUrl,
    'id': id,
    'name': name,
    'email': email,
    'phone': phone,
    'role': role,
    'profileImage': profileImage,
    'roles': roles,
    'permissions': permissions,
    'settings': settings,
  };

  static List<String> _stringList(dynamic value) {
    if (value is! List) return const [];
    return value
        .map((entry) {
          if (entry is Map && entry['name'] != null) return entry['name'];
          return entry;
        })
        .where((entry) => entry != null)
        .map((entry) => entry.toString().toLowerCase())
        .where((entry) => entry.isNotEmpty)
        .toList();
  }

  static Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const {};
  }

  static int? _intOrNull(dynamic value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '');
  }
}
