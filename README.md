# KTV Core

Core functionality plugin for KTV system, providing BlueTAG token-based authentication and essential WordPress features.

## Description

KTV Core is a WordPress plugin that implements secure token-based authentication using BlueTAG technology. It provides a robust API for generating and validating authentication tokens, along with essential WordPress functionality for the KTV system.

## Features

- Secure token-based authentication
- REST API endpoints for login and token validation
- Rate limiting and IP-based access control
- Configurable token expiration
- Automatic admin login with valid tokens

## Installation

1. Upload the `ktv-core` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the BlueTAG settings in the WordPress admin panel

## Configuration

### BlueTAG Settings

1. Navigate to the BlueTAG Settings page in WordPress admin
2. Configure the following settings:
   - API Key: Your BlueTAG API key
   - Username: Your BlueTAG username
   - Token Expiration: Set the token validity period (in seconds)

## API Endpoints

### Generate Login Token

```
POST /wp-json/v1/bluetag_login

Parameters:
- bluetag_api_key: Your BlueTAG API key (required)

Success Response (HTTP 200):
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_at": "2023-12-31T23:59:59Z",
        "login_url": "https://your-site.com/wp-json/v1/bluetag_login?token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
    }
}

Error Responses:

Invalid Credentials (HTTP 401):
{
    "success": false,
    "error": {
        "code": "invalid_credentials",
        "message": "Invalid BlueTAG API key or username"
    }
}

Missing Parameters (HTTP 400):
{
    "success": false,
    "error": {
        "code": "missing_parameters",
        "message": "Required parameters are missing"
    }
}

Rate Limit Exceeded (HTTP 429):
{
    "success": false,
    "error": {
        "code": "rate_limit_exceeded",
        "message": "Too many requests. Please try again later",
        "retry_after": 300
    }
}
```

### Token-Based Login

```
GET /wp-json/v1/bluetag_login?token=your_token

Parameters:
- token: The generated authentication token

Response:
Redirects to WordPress admin upon successful authentication
```

## Security Features

- Rate limiting to prevent brute force attacks
- IP-based access control
- Token expiration
- User agent and IP validation
- Secure token generation using random bytes

## Support

For support and feature requests, please contact the plugin maintainers.

## License

This plugin is licensed under the GPL v2 or later.

Copyright © 2023 KTV System