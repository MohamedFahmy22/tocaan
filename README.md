# Order and Payment Management API

A Laravel-based RESTful API for managing orders and payments with a focus on clean code principles and extensibility. The system uses the **Strategy Pattern** for payment gateways, allowing new payment methods to be added with minimal code changes.

## 🚀 Features

- **Order Management**: Create, read, update, delete orders with status management
- **Payment Processing**: Process payments through multiple gateways
- **Extensible Payment Gateways**: Strategy Pattern for easy gateway addition
- **JWT Authentication**: Secure API access with JSON Web Tokens
- **RESTful Design**: Follows REST principles with proper HTTP methods and status codes
- **Pagination**: All list endpoints support pagination
- **Comprehensive Testing**: Unit and feature tests included

## 📋 Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ or SQLite
- Laravel 12.x

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd tocaan
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tocaan
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Keys

```bash
php artisan key:generate
php artisan jwt:secret
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Start the Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 🔐 Authentication

The API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-token>
```

### Getting a Token

1. Register a new user:
```bash
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```

2. Or login with existing credentials:
```bash
POST /api/auth/login
{
    "email": "john@example.com",
    "password": "Password123!"
}
```

## 📚 API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Logout (invalidate token) |
| POST | `/api/auth/refresh` | Refresh token |
| GET | `/api/auth/me` | Get authenticated user |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List all orders (paginated) |
| POST | `/api/orders` | Create new order |
| GET | `/api/orders/{id}` | Get order details |
| PUT | `/api/orders/{id}` | Update order |
| DELETE | `/api/orders/{id}` | Delete order |
| PATCH | `/api/orders/{id}/confirm` | Confirm order |
| PATCH | `/api/orders/{id}/cancel` | Cancel order |
| GET | `/api/orders/my-orders` | Get user's orders |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments` | List all payments |
| GET | `/api/payments/{id}` | Get payment details |
| GET | `/api/orders/{orderId}/payments` | Get order payments |
| POST | `/api/orders/{orderId}/payments` | Process payment |
| GET | `/api/payment-methods` | List available methods |

## 💳 Payment Gateway Extensibility

The system uses the **Strategy Pattern** to allow easy addition of new payment gateways. Each gateway implements a common interface, making them interchangeable.

### Architecture

```
app/
├── Contracts/
│   └── PaymentGateways/
│       └── PaymentGatewayInterface.php    # Strategy interface
├── PaymentGateways/
│   ├── AbstractPaymentGateway.php         # Base class
│   ├── CreditCardGateway.php              # Concrete strategy
│   ├── PayPalGateway.php                  # Concrete strategy
│   ├── StripeGateway.php                  # Concrete strategy
│   ├── BankTransferGateway.php            # Concrete strategy
│   └── PaymentGatewayFactory.php          # Factory
└── Providers/
    └── PaymentGatewayServiceProvider.php  # DI configuration
```

### Adding a New Payment Gateway

Adding a new payment gateway requires only **3 simple steps**:

#### Step 1: Create the Gateway Class

Create a new class in `app/PaymentGateways/`:

```php
<?php

namespace App\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

class MyNewGateway extends AbstractPaymentGateway
{
    public function getName(): string
    {
        return 'my_new_gateway';
    }

    public function getDisplayName(): string
    {
        return 'My New Gateway';
    }

    public function processPayment(PaymentDTO $payment): PaymentResult
    {
        // Your payment processing logic here
        // Call external API, process payment, etc.
        
        return PaymentResult::success(
            transactionId: 'TXN-123',
            message: 'Payment successful'
        );
    }

    public function refund(string $transactionId, float $amount): RefundResult
    {
        // Your refund logic here
        
        return RefundResult::success(
            refundId: 'REF-123',
            amount: $amount
        );
    }

    public function validateConfiguration(): bool
    {
        return !empty($this->config['api_key']);
    }
}
```

#### Step 2: Add Configuration

Add the gateway configuration to `config/payment_gateways.php`:

```php
'my_new_gateway' => [
    'enabled' => env('GATEWAY_MY_NEW_ENABLED', true),
    'api_key' => env('GATEWAY_MY_NEW_API_KEY'),
    'secret' => env('GATEWAY_MY_NEW_SECRET'),
    'sandbox' => env('GATEWAY_MY_NEW_SANDBOX', true),
],
```

And add environment variables to `.env`:

```env
GATEWAY_MY_NEW_ENABLED=true
GATEWAY_MY_NEW_API_KEY=your-api-key
GATEWAY_MY_NEW_SECRET=your-secret
GATEWAY_MY_NEW_SANDBOX=true
```

#### Step 3: Register the Gateway

Register the gateway in `app/Providers/PaymentGatewayServiceProvider.php`:

```php
private function registerGateways(PaymentGatewayFactory $factory): void
{
    // Existing gateways...
    $factory->register('credit_card', CreditCardGateway::class);
    $factory->register('paypal', PayPalGateway::class);
    
    // Add your new gateway
    $factory->register('my_new_gateway', MyNewGateway::class);
}
```

#### Step 4: Add to PaymentMethod Enum (Optional)

If you want type-safe validation, add to `app/Enums/PaymentMethod.php`:

```php
case MY_NEW_GATEWAY = 'my_new_gateway';
```

**That's it!** Your new gateway is now available for use.

## 📖 Business Rules

1. **Payment Processing**: Payments can only be processed for orders in `confirmed` status
2. **Order Deletion**: Orders cannot be deleted if they have associated payments
3. **Order Cancellation**: Orders with successful payments cannot be cancelled
4. **Order Updates**: Only orders in `pending` status can be updated

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test files
php artisan test --filter=PaymentGateway
php artisan test --filter=OrderTest
php artisan test --filter=AuthTest
```

### Test Coverage

- **Unit Tests**: Payment gateway logic, factory patterns
- **Feature Tests**: API endpoints, authentication, business rules

## 📦 Project Structure

```
app/
├── Contracts/              # Interfaces (Repository, PaymentGateway)
├── DTOs/                   # Data Transfer Objects
├── Enums/                  # Status enumerations
├── Exceptions/             # Custom exceptions
├── Http/
│   ├── Controllers/Api/    # API Controllers
│   ├── Requests/           # Form validation
│   └── Resources/          # API Resources
├── Models/                 # Eloquent models
├── PaymentGateways/        # Gateway implementations
├── Repositories/           # Data access layer
├── Services/               # Business logic
└── ValueObjects/           # Immutable value objects
```

## 🎯 Design Patterns Used

- **Strategy Pattern**: Payment gateway abstraction
- **Repository Pattern**: Data access abstraction
- **Factory Pattern**: Gateway instantiation
- **DTO Pattern**: Data transfer between layers
- **Service Layer**: Business logic encapsulation

## 🔧 Configuration

### Payment Gateways

Configure gateways in `.env`:

```env
# Default gateway
DEFAULT_PAYMENT_GATEWAY=credit_card

# Credit Card Gateway
GATEWAY_CREDIT_CARD_ENABLED=true
GATEWAY_CREDIT_CARD_MERCHANT_ID=your-merchant-id
GATEWAY_CREDIT_CARD_API_KEY=your-api-key
GATEWAY_CREDIT_CARD_SANDBOX=true

# PayPal Gateway
GATEWAY_PAYPAL_ENABLED=true
GATEWAY_PAYPAL_CLIENT_ID=your-client-id
GATEWAY_PAYPAL_CLIENT_SECRET=your-client-secret
GATEWAY_PAYPAL_SANDBOX=true

# Stripe Gateway
GATEWAY_STRIPE_ENABLED=true
GATEWAY_STRIPE_PUBLISHABLE_KEY=pk_test_xxx
GATEWAY_STRIPE_SECRET_KEY=sk_test_xxx
GATEWAY_STRIPE_SANDBOX=true
```

## 📝 API Documentation

A Postman collection is included in the repository:

- `postman_collection.json` - Import into Postman for interactive testing

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 👤 Author

Built as a technical assessment project demonstrating:
- Clean Code principles
- SOLID principles
- Design Patterns
- Laravel best practices
- RESTful API design
- Test-Driven Development
