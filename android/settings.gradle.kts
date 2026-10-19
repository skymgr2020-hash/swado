pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        // Cashfree's Android PG SDK publishes to Maven Central but some of
        // its transitive/older releases are jitpack-only — kept as a fallback.
        maven { url = uri("https://jitpack.io") }
    }
}

rootProject.name = "Swado"
include(":app")
