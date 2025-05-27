# RillTech Platform

> **A cutting-edge no-code AI agent platform with enterprise-grade capabilities**

RillTech is a comprehensive SaaS platform that democratizes AI agent creation through an intuitive interface, powered by advanced RAG (Retrieval-Augmented Generation) technology and modern web technologies.

## 🚀 Key Features

### 🤖 **Advanced AI Integration**

- **RAG-Powered Chat System**: Intelligent responses using Retrieval-Augmented Generation
- **Multi-Model Support**: Mistral AI, OpenAI GPT, and Anthropic Claude integration
- **Real-time Streaming**: Live response streaming for enhanced user experience
- **Memory Persistence**: File-based chat history across sessions
- **Anti-Hallucination**: Strict knowledge base adherence for accurate responses
- **Rate Limiting Handling**: Graceful degradation with user-friendly messages

### 💳 **Enterprise Subscription Management**

- **Stripe Integration**: Full Laravel Cashier implementation
- **Multiple Plans**: Flexible subscription tiers with real-time pricing
- **Payment Methods**: Secure payment method management
- **Subscription Controls**: Cancel, resume, and modify subscriptions
- **Invoice Management**: Automated billing and invoice generation
- **Usage Tracking**: Monitor and bill based on platform usage

### 👥 **Comprehensive User Management**

- **Role-Based Access Control**: Spatie Permission integration
- **User Lifecycle**: Registration, verification, and status management
- **Activity Logging**: Comprehensive audit trails with Spatie Activity Log
- **Permission Groups**: Dynamic permission management
- **Super Admin**: Inherits all permissions with proper middleware

### 🎨 **Modern Frontend Experience**

- **Vue.js 3.5.13**: Progressive JavaScript framework with TypeScript
- **Tailwind CSS 4.1.1**: Utility-first CSS with custom components
- **Inertia.js 2.0**: SPA functionality without API complexity
- **GSAP Animations**: Smooth, professional animations
- **Responsive Design**: Optimized for all device types
- **Real-time Updates**: Laravel Reverb for WebSocket connections

## 🛠 Technology Stack

### **Backend Technologies**

```
Laravel 12.0           - PHP web application framework
PHP 8.2+               - Server-side scripting language
MySQL/PostgreSQL      - Relational database systems
Redis                  - In-memory data structure store
Laravel Cashier 15.6   - Stripe subscription billing
Spatie Packages        - Permission and activity logging
Laravel Reverb 1.0     - WebSocket server for real-time features
```

### **Frontend Technologies**

```
Vue.js 3.5.13          - Progressive JavaScript framework
TypeScript 5.2.2       - Type-safe JavaScript development
Tailwind CSS 4.1.1     - Utility-first CSS framework
Vite 6.2.0             - Fast build tool and dev server
GSAP 3.13.0            - Animation library
Inertia.js 2.0.0       - SPA framework for Laravel
```

### **AI & ML Technologies**

```
Neuron AI 1.9          - AI agent orchestration framework
Mistral AI             - Primary language model provider
Voyage AI              - Text embedding generation
Pinecone               - Vector database for RAG
OpenAI GPT-4           - Alternative language model
Anthropic Claude       - Safety-focused AI model
```

### **Development & Deployment**

```
Docker                 - Containerization
Pest/PHPUnit          - Testing frameworks
Laravel Pint          - Code formatting
ESLint/Prettier       - Frontend code quality
Concurrently          - Development workflow
```

## 🏗 Architecture Overview

### **AI Agent System**

- **RillTechAgent**: Main AI agent extending Neuron AI's RAG class
- **SafeVoyageEmbeddingsProvider**: Rate-limiting aware embeddings
- **SafePineconeVectorStore**: Error-handling vector store
- **Tool Integration**: Modular tools for specific functionalities
- **Memory Management**: Persistent chat history with context windows

### **Subscription System**

- **Laravel Cashier**: Stripe integration for payments
- **Subscription Middleware**: Access control based on subscription status
- **Plan Management**: Dynamic plan creation and modification
- **Payment Methods**: Secure card management with Stripe Elements
- **Billing Automation**: Automated invoicing and payment processing

### **User Management**

- **Spatie Roles & Permissions**: Granular access control
- **User Status Management**: Active, suspended, banned states
- **Activity Logging**: Comprehensive audit trails
- **Authentication**: Laravel's built-in authentication system
- **Profile Management**: User profile and settings

## 📁 Project Structure

```
app/
├── AI/                          # AI Agent Implementation
│   ├── RillTechAgent.php       # Main RAG-powered AI agent
│   ├── SafeVoyageEmbeddingsProvider.php
│   ├── SafePineconeVectorStore.php
│   ├── Providers/              # AI model providers
│   └── Tools/                  # AI agent tools
├── Http/
│   ├── Controllers/
│   │   ├── ChatController.php  # Chat API endpoints
│   │   ├── Client/             # Client dashboard controllers
│   │   └── Admin/              # Admin panel controllers
│   └── Middleware/
│       ├── CheckSubscription.php
│       └── VerifyCsrfToken.php
├── Models/
│   └── User.php               # User model with Billable trait
└── ...

resources/
├── js/
│   ├── Components/            # Vue.js components
│   ├── Pages/                # Inertia.js pages
│   └── Layouts/              # Application layouts
└── css/
    └── app.css               # Tailwind CSS styles

routes/
├── web.php                   # Web routes
├── api.php                   # API routes
├── client.php               # Client dashboard routes
└── admin.php                # Admin panel routes
```

## 🚀 Getting Started

### **Prerequisites**

- PHP 8.2 or higher
- Node.js 18+ and npm
- MySQL/PostgreSQL database
- Redis server
- Composer

### **Environment Setup**

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd RillTech
    ```

2. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

3. **Environment configuration**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Configure your `.env` file**

    ```env
    # Database
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=rilltech
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    # Redis
    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379

    # Stripe (for subscriptions)
    STRIPE_KEY=pk_test_...
    STRIPE_SECRET=sk_test_...
    STRIPE_WEBHOOK_SECRET=whsec_...

    # AI Services
    MISTRAL_API_KEY=your_mistral_key
    VOYAGE_API_KEY=your_voyage_key
    VOYAGE_MODEL=voyage-3-large
    PINECONE_API_KEY=your_pinecone_key
    PINECONE_INDEX_URL=https://your-index.pinecone.io
    ```

5. **Database setup**

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

6. **Storage setup**
    ```bash
    php artisan storage:link
    mkdir -p storage/app/chat_history
    ```

### **Development Workflow**

**Start development servers:**

```bash
composer run dev
```

This command starts:

- Laravel development server (port 8000)
- Queue worker for background jobs
- Laravel Pail for real-time logs
- Vite development server for frontend

**Alternative commands:**

```bash
# Backend only
php artisan serve

# Frontend only
npm run dev

# Queue worker
php artisan queue:listen

# Real-time logs
php artisan pail
```

## 🧪 Testing

### **Run Tests**

```bash
# All tests
composer run test

# Specific test file
php artisan test tests/Feature/ChatTest.php

# With coverage
php artisan test --coverage
```

### **Code Quality**

```bash
# Format code
./vendor/bin/pint

# Frontend linting
npm run lint

# Type checking
npm run format:check
```

## 🔧 Configuration

### **AI Agent Configuration**

The AI agent can be configured in `app/AI/RillTechAgent.php`:

```php
// Change AI model
protected function provider(): AIProviderInterface
{
    return new MistralAI(
        key: config('services.mistral.api_key'),
        model: 'mistral-large-latest', // Change model here
    );
}

// Adjust context window
protected function chatHistory(): AbstractChatHistory
{
    return new FileChatHistory(
        directory: storage_path('app/chat_history'),
        key: $sessionId,
        contextWindow: 50000 // Adjust context size
    );
}
```

### **Subscription Plans**

Configure subscription plans in your Stripe dashboard and update the frontend pricing components.

### **Permissions & Roles**

Manage roles and permissions through the admin dashboard or via seeders:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

## 🔌 API Endpoints

### **Chat API**

```
POST /api/chat              # Send message to AI agent
POST /api/chat/stream       # Streaming chat responses
```

### **Subscription API**

```
POST /subscription/checkout # Create Stripe checkout session
GET  /subscription          # Get subscription details
POST /subscription/cancel   # Cancel subscription
POST /subscription/resume   # Resume subscription
```

### **User Management**

```
GET  /admin/users          # List users (admin)
POST /admin/users          # Create user (admin)
PUT  /admin/users/{id}     # Update user (admin)
```

## 🛡 Security Features

### **Authentication & Authorization**

- Laravel's built-in authentication system
- Role-based access control with Spatie Permission
- CSRF protection for all forms
- Rate limiting on API endpoints
- Secure session management

### **Data Protection**

- Input validation and sanitization
- SQL injection prevention through Eloquent ORM
- XSS protection with Laravel's built-in features
- Secure password hashing with bcrypt
- Environment variable protection

### **Payment Security**

- Stripe's secure payment processing
- PCI DSS compliance through Stripe
- Webhook signature verification
- Secure customer data handling

## 📊 Monitoring & Analytics

### **Application Monitoring**

- Laravel Pail for real-time log monitoring
- Comprehensive error logging
- Performance monitoring with built-in tools
- Queue job monitoring

### **User Analytics**

- Spatie Activity Log for user actions
- Chat interaction tracking
- Subscription analytics
- Usage pattern analysis

## 🚀 Deployment

### **Production Setup**

1. **Server Requirements**

    - PHP 8.2+ with required extensions
    - MySQL/PostgreSQL database
    - Redis server
    - Web server (Nginx/Apache)
    - SSL certificate

2. **Environment Configuration**

    ```bash
    # Set production environment
    APP_ENV=production
    APP_DEBUG=false

    # Configure production database
    DB_CONNECTION=mysql
    DB_HOST=your-production-host

    # Set production URLs
    APP_URL=https://your-domain.com
    ```

3. **Deployment Commands**

    ```bash
    # Install dependencies
    composer install --optimize-autoloader --no-dev
    npm ci && npm run build

    # Configure application
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Run migrations
    php artisan migrate --force
    ```

### **Docker Deployment**

```dockerfile
# Use the official PHP image
FROM php:8.2-fpm

# Install dependencies and configure
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Copy application files
COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev
```

## 🤝 Contributing

### **Development Guidelines**

1. Follow PSR-12 coding standards
2. Write comprehensive tests for new features
3. Use conventional commit messages
4. Update documentation for API changes
5. Ensure all tests pass before submitting PRs

### **Code Style**

```bash
# Format PHP code
./vendor/bin/pint

# Format frontend code
npm run format

# Run linting
npm run lint
```

## 📝 License

This project is proprietary software. All rights reserved.

## 🆘 Support

### **Documentation**

- API Documentation: `/docs/api`
- User Guide: `/docs/user-guide`
- Developer Guide: `/docs/developer`

### **Getting Help**

- GitHub Issues: For bug reports and feature requests
- Email Support: support@rilltech.com
- Community Forum: Available for subscribers

## 🔄 Changelog

### **Version 1.0.0** (Current)

- ✅ RAG-powered AI agent with Mistral AI integration
- ✅ Comprehensive subscription management with Stripe
- ✅ Role-based user management system
- ✅ Real-time chat with streaming responses
- ✅ Anti-hallucination safeguards
- ✅ Rate limiting and error handling
- ✅ Modern Vue.js frontend with TypeScript
- ✅ Enterprise-grade security features

---

**Built with ❤️ by the RillTech Team**

_Democratizing AI agent creation for businesses of all sizes_
