import 'dart:ui';

import 'package:flutter/material.dart';

import '../theme/onboard_theme.dart';

class OnboardScaffold extends StatelessWidget {
  const OnboardScaffold({
    super.key,
    required this.body,
    this.appBar,
    this.bottomNavigationBar,
    this.safeArea = true,
  });

  final Widget body;
  final PreferredSizeWidget? appBar;
  final Widget? bottomNavigationBar;
  final bool safeArea;

  @override
  Widget build(BuildContext context) {
    Widget content = body;
    if (safeArea) {
      content = SafeArea(bottom: bottomNavigationBar == null, child: content);
    }

    return Scaffold(
      backgroundColor: OnboardColors.canvas,
      appBar: appBar,
      bottomNavigationBar: bottomNavigationBar,
      body: Stack(
        children: [
          const Positioned.fill(child: _AuroraBackdrop()),
          Positioned.fill(child: content),
        ],
      ),
    );
  }
}

class _AuroraBackdrop extends StatelessWidget {
  const _AuroraBackdrop();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(color: OnboardColors.canvas),
      child: Stack(
        children: [
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    OnboardColors.auroraA.withValues(alpha: 0.18),
                    Colors.white.withValues(alpha: 0.08),
                    OnboardColors.auroraB.withValues(alpha: 0.16),
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            left: -90,
            top: -120,
            width: 360,
            height: 360,
            child: _Glow(color: OnboardColors.auroraA.withValues(alpha: 0.35)),
          ),
          Positioned(
            right: -100,
            top: 120,
            width: 360,
            height: 360,
            child: _Glow(color: OnboardColors.auroraB.withValues(alpha: 0.28)),
          ),
          Positioned(
            left: 80,
            bottom: -160,
            width: 420,
            height: 420,
            child: _Glow(color: OnboardColors.auroraC.withValues(alpha: 0.24)),
          ),
        ],
      ),
    );
  }
}

class _Glow extends StatelessWidget {
  const _Glow({required this.color});

  final Color color;

  @override
  Widget build(BuildContext context) {
    return ImageFiltered(
      imageFilter: ImageFilter.blur(sigmaX: 70, sigmaY: 70),
      child: DecoratedBox(
        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
      ),
    );
  }
}

class GlassPanel extends StatelessWidget {
  const GlassPanel({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.margin = EdgeInsets.zero,
    this.radius = 18,
    this.onTap,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry margin;
  final double radius;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final borderRadius = BorderRadius.circular(radius);
    final panel = ClipRRect(
      borderRadius: borderRadius,
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: OnboardColors.glassSurface,
            borderRadius: borderRadius,
            border: Border.all(color: OnboardColors.glassBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.07),
                blurRadius: 24,
                offset: const Offset(0, 14),
              ),
            ],
          ),
          child: Padding(padding: padding, child: child),
        ),
      ),
    );

    if (onTap == null) return Padding(padding: margin, child: panel);

    return Padding(
      padding: margin,
      child: Material(
        color: Colors.transparent,
        child: InkWell(onTap: onTap, borderRadius: borderRadius, child: panel),
      ),
    );
  }
}

class GlassAppBar extends StatelessWidget implements PreferredSizeWidget {
  const GlassAppBar({super.key, this.title, this.actions});

  final Widget? title;
  final List<Widget>? actions;

  @override
  Size get preferredSize => const Size.fromHeight(64);

  @override
  Widget build(BuildContext context) {
    return AppBar(
      title: title,
      actions: actions,
      toolbarHeight: 64,
      titleSpacing: 20,
      backgroundColor: Colors.transparent,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      flexibleSpace: ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: OnboardColors.canvas.withValues(alpha: 0.66),
              border: Border(
                bottom: BorderSide(color: OnboardColors.glassBorder),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class GlassBottomBar extends StatelessWidget {
  const GlassBottomBar({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return ClipRect(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: OnboardColors.canvas.withValues(alpha: 0.74),
            border: const Border(top: BorderSide(color: OnboardColors.line)),
          ),
          child: SafeArea(top: false, child: child),
        ),
      ),
    );
  }
}

class GlassButton extends StatelessWidget {
  const GlassButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon,
    this.loading = false,
    this.compact = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool loading;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || loading;
    final height = compact ? 42.0 : 54.0;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: disabled ? null : onPressed,
        borderRadius: BorderRadius.circular(compact ? 12 : 16),
        child: Opacity(
          opacity: disabled ? 0.62 : 1,
          child: Container(
            height: height,
            alignment: Alignment.center,
            padding: EdgeInsets.symmetric(horizontal: compact ? 14 : 20),
            decoration: BoxDecoration(
              gradient: OnboardColors.gradient,
              borderRadius: BorderRadius.circular(compact ? 12 : 16),
              boxShadow: [
                BoxShadow(
                  color: OnboardColors.primary.withValues(alpha: 0.28),
                  blurRadius: 18,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: loading
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (icon != null) ...[
                        Icon(icon, color: Colors.white, size: 18),
                        const SizedBox(width: 8),
                      ],
                      Text(
                        label,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: compact ? 14 : 16,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

class OnboardSectionTitle extends StatelessWidget {
  const OnboardSectionTitle({super.key, required this.title, this.subtitle});

  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleLarge),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Text(subtitle!, style: Theme.of(context).textTheme.bodySmall),
        ],
      ],
    );
  }
}
