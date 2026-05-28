# Two-Factor Authentication

Two-factor authentication (2FA) adds a second verification step when signing in. After entering your password you are asked for a time-based one-time code from an authenticator app. Even if your password is compromised, an attacker cannot sign in without physical access to your device.

## Requirements

You need an authenticator app installed on your phone or computer. Any TOTP-compatible app works:

- **Google Authenticator** (Android / iOS)
- **Authy** (Android / iOS / desktop)
- **1Password**, **Bitwarden**, **Dashlane** (built-in TOTP)
- **Apple Passwords** (iOS 17+ / macOS Sonoma+)
- **Microsoft Authenticator**

## Enabling 2FA

1. Go to your **Profile** page (`/profile`).
2. Scroll to the **Two-factor authentication** panel and click **Enable 2FA**.
3. Scan the QR code with your authenticator app. If your app doesn't support QR scanning, enter the text key shown beneath the code manually.
4. Your app will display a 6-digit code that changes every 30 seconds. Type the current code into the **Confirmation code** field and click **Confirm & enable**.
5. A set of **recovery codes** is shown immediately after activation. Save these codes in a secure location (password manager, printed paper, etc.). Each code can be used once to sign in if you ever lose access to your authenticator app.

2FA is now active. The panel shows an **Enabled** badge.

## Signing In with 2FA

1. Enter your email and password as usual and click **Sign in**.
2. You are redirected to the **Two-factor authentication** page.
3. Open your authenticator app, find the Lambda CMS entry, and type the 6-digit code.
4. Click **Verify** to complete sign-in.

The code changes every 30 seconds. If it expires while you are typing, wait for the next one — codes are accepted for a short window on either side of the displayed period.

## Using a Recovery Code

If you don't have access to your authenticator app, click **Use a recovery code instead** on the challenge page, enter one of your saved recovery codes, and click **Verify**.

Each recovery code is single-use and removed from your account immediately after it is accepted. If you are running low on unused codes, regenerate them from your Profile page while you still have access.

## Managing Recovery Codes

In the **Two-factor authentication** panel on your Profile page:

- Click **View codes** to display your current recovery codes.
- Click **Regenerate codes** to replace all existing codes with a new set. Do this after you have used several codes, or if you suspect the old codes were exposed. **Save the new codes immediately** — the old ones are invalidated as soon as you regenerate.

## Disabling 2FA

1. Go to **Profile → Two-factor authentication** and click **Disable 2FA**.
2. Confirm the action in the prompt that appears.

2FA is removed from your account and you will no longer be asked for a code on sign-in.

::: warning
Disabling 2FA makes your account less secure. Only do this if you are switching authenticator apps or devices. Re-enable it immediately after.
:::

## Troubleshooting

**The code is rejected.** Codes are time-based — make sure your phone's clock is set to automatic / network time. A clock that is even a minute off will cause mismatches.

**I lost my phone and my recovery codes.** Contact an administrator to delete your account and create a new one, or ask them to directly clear the 2FA columns (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`) in the database.

**I changed my email address.** Your existing 2FA setup continues to work — the TOTP secret is bound to your account, not your email. The QR code label in your authenticator app will show your old email until you remove and re-add the entry.
