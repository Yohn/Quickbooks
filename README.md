# Quickbooks
> [!WARNING]
> Asked Claude AI how to connect  to Quickbooks, and this was the response
---
> [!TIP]
> ### How to use PHP to do CRUD operations with QuickBooks?

I'll show you how to perform CRUD operations with QuickBooks using PHP. The most common approach is using the QuickBooks Web Connector (QBWC) or the QuickBooks Online API.This PHP class provides comprehensive CRUD operations for QuickBooks Online. Here are the key features:

**Setup Requirements:**
- PHP 8.2+ with cURL extension
- QuickBooks Online developer account
- OAuth 1.0a credentials (consumer key/secret, access tokens)

**Key Operations:**
- **Create**: Add customers, items, and invoices
- **Read**: Retrieve individual records or query multiple records
- **Update**: Modify existing records (requires SyncToken)
- **Delete**: Deactivate records (QB doesn't allow true deletion)

**Important Notes:**
- QuickBooks uses OAuth 1.0a for authentication
- Updates require the current `SyncToken` to prevent conflicts
- "Deletion" actually sets records to inactive status
- All requests use proper error handling and validation

**To get started:**
1. Register your app at https://developer.intuit.com
2. Obtain OAuth credentials through the authorization flow
3. Replace the placeholder credentials in the usage example
4. Install any required dependencies for production use

The class handles OAuth signature generation, HTTP requests, and error handling. You can extend it to support additional QuickBooks entities like vendors, employees, or payments by following the same patterns.

---
> [!IMPORTANT]
> ### Do we have to pay for thsi integration with quickbooks?

Yes, there are costs associated with QuickBooks integration, but it depends on what type of integration you're building:

## QuickBooks Online API Costs

**Good news**: There's no additional costs associated with using QuickBooks' API for QuickBooks Online. The API itself is free to use, but there are some considerations:

### What You Need to Pay For:

1. **QuickBooks Online Subscription** - Your customers need an active QuickBooks Online subscription:
   - QuickBooks Online pricing starts at just $10 per month, thanks to a special deal that gets you an extra low price for the first three months
   - QuickBooks Online pricing ranges from $38 to $275 per month for regular pricing
   - Plans: Simple Start ($38/month), Essentials ($55/month), Plus ($80/month), Advanced ($200/month)

2. **Rate Limits** - QuickBooks Online has API call limits and throttles to keep in mind, but no charges for API calls

## New Changes Coming in 2025

**Important Update**: On May 15, 2025, Intuit announced a significant change in how developers interact with its QuickBooks and Intuit Enterprise platforms: the launch of the App Partner Program, which introduces platform service fees for developers based on a tiered model

Starting July 28, 2025, Intuit is introducing a new App Partner Program with:
- A free tier for developers getting started
- Paid tiers with monthly fees for enhanced features
- Developers who exceed their tier's API limits will be subject to metered usage fees starting November 1, 2025

## QuickBooks Desktop API Costs

For QuickBooks Desktop integration, there are costs:
- Connections to the QBD API will be sold in packages of 200 connections for $1,000 annually

## Bottom Line

**For now**: QuickBooks Online API access is free, but your customers need QB subscriptions. **Starting late 2025**: New tiered pricing may apply depending on your usage and requirements.

If you're just getting started with development, you can begin with the free tier, but plan for potential costs as your integration scales.
