<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile app links (Android App Links / iOS Universal Links)
    |--------------------------------------------------------------------------
    |
    | These drive the two /.well-known files an operating system fetches
    | before it will let this domain's links open the app instead of the
    | browser. Today that is used by the movie-theatre seat QR stickers:
    | a scanned armrest sticker opens the app on the right seat, and falls
    | back to the web ordering page when the app is not installed.
    |
    | Both files are served by App\Http\Controllers\AppLinksController.
    |
    */

    'android' => [
        'package' => env('ANDROID_APP_PACKAGE', 'com.renjo.app'),

        /*
         | SHA-256 fingerprints of the certificates that sign the app,
         | comma-separated. List every certificate that is in play at once --
         | Android accepts any of them, and getting this wrong is the single
         | most common reason links silently open in the browser instead.
         |
         | With Play App Signing (the default for new apps), the build that
         | users install is re-signed by Google, so the fingerprint that
         | matters is the "App signing key certificate" shown under
         | Play Console > Test and release > Setup > App signing -- NOT the
         | upload key. Keep the upload key listed too so sideloaded and
         | internal-test builds keep working.
         |
         | Default below is the local release keystore (the Swado release keystore).
         */
        'sha256_fingerprints' => env(
            'ANDROID_APP_SHA256_FINGERPRINTS',
            '70:CB:4D:5F:21:D6:18:1E:89:2F:06:77:37:09:76:9D:0B:8B:49:25:AF:0D:CD:60:38:D0:FC:E6:7C:7B:2A:CD'
        ),
    ],

    'ios' => [
        'bundle_id' => env('IOS_APP_BUNDLE_ID', 'com.renjo.app'),

        /*
         | The Apple Developer Team ID that owns the bundle id (Membership
         | page in the developer portal, or the prefix of the App ID).
         |
         | Deliberately has no default: serving an apple-app-site-association
         | with a wrong team id is worse than serving none, because iOS
         | caches it and universal links then fail silently for everyone who
         | fetched the bad copy. Until this is set the AASA endpoint returns
         | 404, which is an obvious, fixable state.
         */
        'team_id' => env('IOS_APP_TEAM_ID'),
    ],

    /*
     | Paths that should open the app. Everything else on this domain --
     | the marketing site, the legal pages, the admin panel -- stays in the
     | browser. Android path scoping lives in AndroidManifest.xml instead;
     | assetlinks.json cannot express it.
     */
    'paths' => [
        '/t/*',
    ],

];
