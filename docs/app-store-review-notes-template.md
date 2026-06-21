# App Store Review Notes Template

## Sign-In Required

- Username: `review.manager@your-domain.com`
- Password: `YOUR_MANAGER_PASSWORD`

## Notes For Review

```text
This app requires sign-in and uses role-based access.

Main review account:
Role: Manager
Email: review.manager@your-domain.com
Password: YOUR_MANAGER_PASSWORD

Use the Manager account to review the main workflow:
- Work Orders list
- Work Order details
- Materials
- Profile
- Create Draft
- Storage update

Additional review accounts for role-specific UI:

Paint account
Role: Paint
Email: review.paint@your-domain.com
Password: YOUR_PAINT_PASSWORD

Use this account to review:
- Paint top menu
- WO / Lost tabs
- Paint date editing
- Owner message flow
- Lost part add / delete

Machining account
Role: Machining
Email: review.machining@your-domain.com
Password: YOUR_MACHINING_PASSWORD

Use this account to review:
- Machining top menu
- My WO toggle
- Hide closed toggle
- Machining step date editing
- Machining photo upload
- Machining PDF upload / list / delete

Optional Shipping account
Role: Shipping
Email: review.shipping@your-domain.com
Password: YOUR_SHIPPING_PASSWORD

Use this account to review:
- Shipping-specific top menu
- Draft creation
- Storage update
- Work order detail without Tasks / Parts / Process tabs

Important notes:
- No MFA / OTP is required for review accounts.
- Review accounts are active and do not expire.
- Backend services are enabled for App Review.
- Test data is preloaded for all review accounts.
- The app behavior depends on user role, so please sign out and sign in with the additional accounts to review role-specific screens.

Suggested review steps:
1. Sign in with the Manager account.
2. Open Work Orders, Materials, Profile, and Create Draft.
3. Sign out and sign in with the Paint account to review Paint-specific screens.
4. Sign out and sign in with the Machining account to review Machining-specific screens.
5. Optionally sign in with the Shipping account to review the Shipping-specific workflow.

If needed, we can provide additional test accounts or instructions during review.
```

## Fill Before Submission

Replace before submitting:

- `review.manager@your-domain.com`
- `review.paint@your-domain.com`
- `review.machining@your-domain.com`
- `review.shipping@your-domain.com`
- `YOUR_MANAGER_PASSWORD`
- `YOUR_PAINT_PASSWORD`
- `YOUR_MACHINING_PASSWORD`
- `YOUR_SHIPPING_PASSWORD`

## Recommended Practice

For this app, it is better to provide multiple review accounts, because the top menu and available screens depend on the role:

- `Manager` for the general workflow
- `Paint` for paint-specific screens
- `Machining` for machining-specific screens
- `Shipping` optionally for shipping-specific shell behavior
