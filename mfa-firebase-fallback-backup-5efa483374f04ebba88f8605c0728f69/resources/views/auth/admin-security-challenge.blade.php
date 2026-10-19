@extends('layouts.admin-security')
@section('content')
    @if(! $challenge->verified_at)
        <h1>Verify your mobile</h1>
        <p>Enter the 6-digit SMS code sent to {{ \App\Support\AdminMfa::maskPhone($challenge->payload['phone']) }}. It expires in 5 minutes.</p>
        @if($challenge->purpose === 'phone_authorize')<p>First verify your current recovery mobile. Then we will send a separate code to your new number.</p>@endif
        <form method="POST" action="{{ route('admin-mfa.security.verify') }}">
            @csrf
            <label for="otp">SMS verification code</label>
            <input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
            <button type="submit">Verify mobile</button>
        </form>
        <form method="POST" action="{{ route('admin-mfa.security.resend') }}">@csrf<button type="submit" class="secondary">Resend code</button></form>
        <p>You can resend after 60 seconds. Only the latest code will work.</p>
    @elseif($challenge->purpose === 'disable')
        <h1>Disable MFA?</h1>
        <p>Your mobile has been verified. Disabling MFA removes your authenticator and recovery codes. Future admin logins will use your password alone.</p>
        <form method="POST" action="{{ route('admin-mfa.security.confirm') }}">
            @csrf<input type="hidden" name="confirm" value="1">
            <button type="submit" class="danger">Confirm: disable MFA</button>
        </form>
    @else
        <h1>{{ $challenge->purpose === 'enable' ? 'Enable your authenticator' : 'Register your new authenticator' }}</h1>
        <p>Scan this QR with your authenticator app, then enter its 6-digit code to finish. Your security settings change only after confirmation.</p>
        <div class="qr" role="img" aria-label="New authenticator QR code">{!! $qrCode !!}</div>
        <details><summary>Cannot scan the QR?</summary><p>Use this key for a time-based account:</p><code>{{ $secret }}</code></details>
        <form method="POST" action="{{ route('admin-mfa.security.confirm') }}">
            @csrf<input type="hidden" name="confirm" value="1">
            <label for="code">New authenticator code</label>
            <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
            <button type="submit">Confirm new authenticator</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin-mfa.security.cancel') }}">@csrf<button type="submit" class="secondary">Cancel / choose another mobile</button></form>
@endsection
