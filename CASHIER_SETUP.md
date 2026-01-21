# Laravel Cashier (Stripe) Setup Complete

## ✅ Installation Steps Completed

1. **Installed Laravel Cashier** - v16.2.0
2. **Published Configuration & Migrations**
3. **Updated User Model** - Added `Billable` trait
4. **Ran Migrations** - Created necessary database tables
5. **Configured Environment Variables**

## 📋 Database Tables Created

- `customer_columns` - Stripe customer data on users table
- `subscriptions` - User subscriptions
- `subscription_items` - Subscription line items
- Additional columns for metered billing support

## 🔑 Environment Variables

Add your Stripe API keys to `.env`:

```dotenv
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

## 📚 Next Steps

### 1. Get Stripe Keys
- Sign up at [stripe.com](https://stripe.com)
- Get your test keys from [Dashboard → Developers → API keys](https://dashboard.stripe.com/test/apikeys)

### 2. Setup Webhooks (for production)
- Add webhook endpoint: `https://yourdomain.com/stripe/webhook`
- Copy the webhook secret to `STRIPE_WEBHOOK_SECRET`

### 3. Example Usage

#### Create a Subscription
```php
use Illuminate\Http\Request;

Route::post('/user/subscribe', function (Request $request) {
    $request->user()->newSubscription('default', 'price_monthly')
        ->create($request->paymentMethodId);

    return redirect('/dashboard')->with('message', 'Subscribed successfully!');
});
```

#### Charge a Customer
```php
$user->charge(1000, $paymentMethod); // $10.00 in cents
```

#### Access Billing Portal
```php
Route::get('/billing-portal', function (Request $request) {
    return $request->user()->redirectToBillingPortal();
});
```

#### Check Subscription Status
```php
if ($user->subscribed('default')) {
    // User has an active subscription...
}

if ($user->subscribedToPrice('price_monthly', 'default')) {
    // User is subscribed to a specific price...
}
```

## 🧪 Testing

```php
use Laravel\Cashier\Cashier;

// In your tests
Cashier::fake();
```

## 📖 Documentation

- [Laravel Cashier Docs](https://laravel.com/docs/12.x/billing)
- [Stripe API Docs](https://stripe.com/docs/api)
- [Stripe Testing](https://stripe.com/docs/testing)

## Test Cards

```
Success: 4242 4242 4242 4242
3D Secure: 4000 0025 0000 3155
Declined: 4000 0000 0000 0002
```

Use any future expiry date and any 3-digit CVC.
