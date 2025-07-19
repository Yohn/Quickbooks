<?php

/**
 * QuickBooks CRUD Operations Class
 * Supports both QuickBooks Desktop (via Web Connector) and QuickBooks Online
 */
class QuickBooksCRUD {
	private string $consumerKey;
	private string $consumerSecret;
	private string $accessToken;
	private string $accessTokenSecret;
	private string $realmId;
	private string $baseUrl;
	private bool   $sandbox;

	public function __construct(
		string $consumerKey,
		string $consumerSecret,
		string $accessToken,
		string $accessTokenSecret,
		string $realmId,
		bool $sandbox = true
	) {
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessTokenSecret = $accessTokenSecret;
		$this->realmId = $realmId;
		$this->sandbox = $sandbox;
		$this->baseUrl = $sandbox
			? 'https://sandbox-quickbooks.api.intuit.com'
			: 'https://quickbooks.api.intuit.com';
	}

	/**
	 * CREATE - Add a new customer
	 */
	public function createCustomer(array $customerData): array {
		$endpoint = "/v3/company/{$this->realmId}/customer";

		$customer = [
			'Customer' => [
				'Name'             => $customerData['name'],
				'CompanyName'      => $customerData['company'] ?? null,
				'BillAddr'         => [
						'Line1'                  => $customerData['address'] ?? null,
						'City'                   => $customerData['city'] ?? null,
						'Country'                => $customerData['country'] ?? 'US',
						'CountrySubDivisionCode' => $customerData['state'] ?? null,
						'PostalCode'             => $customerData['zip'] ?? null
					],
				'PrimaryPhone'     => [
					'FreeFormNumber' => $customerData['phone'] ?? null
				],
				'PrimaryEmailAddr' => [
					'Address' => $customerData['email'] ?? null
				]
			]
		];

		return $this->makeRequest('POST', $endpoint, $customer);
	}

	/**
	 * READ - Get customer by ID
	 */
	public function getCustomer(string $customerId): array {
		$endpoint = "/v3/company/{$this->realmId}/customer/{$customerId}";
		return $this->makeRequest('GET', $endpoint);
	}

	/**
	 * READ - Get all customers
	 */
	public function getAllCustomers(): array {
		$endpoint = "/v3/company/{$this->realmId}/query";
		$query = "SELECT * FROM Customer";
		return $this->makeRequest('GET', $endpoint . "?query=" . urlencode($query));
	}

	/**
	 * UPDATE - Update existing customer
	 */
	public function updateCustomer(string $customerId, array $customerData): array {
		// First, get the current customer to get the SyncToken
		$currentCustomer = $this->getCustomer($customerId);

		if (!isset($currentCustomer['Customer'])) {
			throw new Exception('Customer not found');
		}

		$endpoint = "/v3/company/{$this->realmId}/customer";

		$customer = [
			'Customer' => [
				'Id'               => $customerId,
				'SyncToken'        => $currentCustomer['Customer']['SyncToken'],
				'Name'             => $customerData['name'] ?? $currentCustomer['Customer']['Name'],
				'CompanyName'      => $customerData['company'] ?? $currentCustomer['Customer']['CompanyName'] ?? null,
				'BillAddr'         => [
					'Line1'                  => $customerData['address'] ?? $currentCustomer['Customer']['BillAddr']['Line1'] ?? null,
					'City'                   => $customerData['city'] ?? $currentCustomer['Customer']['BillAddr']['City'] ?? null,
					'Country'                => $customerData['country'] ?? $currentCustomer['Customer']['BillAddr']['Country'] ?? 'US',
					'CountrySubDivisionCode' => $customerData['state'] ?? $currentCustomer['Customer']['BillAddr']['CountrySubDivisionCode'] ?? null,
					'PostalCode'             => $customerData['zip'] ?? $currentCustomer['Customer']['BillAddr']['PostalCode'] ?? null
				],
				'PrimaryPhone'     => [
					'FreeFormNumber' => $customerData['phone'] ?? $currentCustomer['Customer']['PrimaryPhone']['FreeFormNumber'] ?? null
				],
				'PrimaryEmailAddr' => [
					'Address' => $customerData['email'] ?? $currentCustomer['Customer']['PrimaryEmailAddr']['Address'] ?? null
				]
			]
		];

		return $this->makeRequest('POST', $endpoint, $customer);
	}

	/**
	 * DELETE - Delete customer (actually makes inactive)
	 */
	public function deleteCustomer(string $customerId): array {
		// Get current customer first
		$currentCustomer = $this->getCustomer($customerId);

		if (!isset($currentCustomer['Customer'])) {
			throw new Exception('Customer not found');
		}

		$endpoint = "/v3/company/{$this->realmId}/customer";

		$customer = [
			'Customer' => [
				'Id'        => $customerId,
				'SyncToken' => $currentCustomer['Customer']['SyncToken'],
				'Active'    => false
			]
		];

		return $this->makeRequest('POST', $endpoint, $customer);
	}

	/**
	 * CREATE - Add a new item
	 */
	public function createItem(array $itemData): array {
		$endpoint = "/v3/company/{$this->realmId}/item";

		$item = [
			'Item' => [
				'Name'              => $itemData['name'],
				'Description'       => $itemData['description'] ?? null,
				'Type'              => $itemData['type'] ?? 'Inventory',
				'UnitPrice'         => $itemData['price'] ?? 0,
				'IncomeAccountRef'  => [
					'value' => $itemData['income_account_id'] ?? '1'
				],
				'ExpenseAccountRef' => [
						'value' => $itemData['expense_account_id'] ?? '2'
					],
				'AssetAccountRef'   => [
						'value' => $itemData['asset_account_id'] ?? '3'
					]
			]
		];

		return $this->makeRequest('POST', $endpoint, $item);
	}

	/**
	 * CREATE - Create an invoice
	 */
	public function createInvoice(array $invoiceData): array {
		$endpoint = "/v3/company/{$this->realmId}/invoice";

		$invoice = [
			'Invoice' => [
				'CustomerRef' => [
					'value' => $invoiceData['customer_id']
				],
				'Line'        => []
			]
		];

		// Add line items
		foreach ($invoiceData['line_items'] as $index => $lineItem) {
			$invoice['Invoice']['Line'][] = [
				'Id'                  => $index + 1,
				'LineNum'             => $index + 1,
				'Amount'              => $lineItem['amount'],
				'DetailType'          => 'SalesItemLineDetail',
				'SalesItemLineDetail' => [
					'ItemRef'   => [
						'value' => $lineItem['item_id']
					],
					'Qty'       => $lineItem['quantity'] ?? 1,
					'UnitPrice' => $lineItem['unit_price'] ?? $lineItem['amount']
				]
			];
		}

		return $this->makeRequest('POST', $endpoint, $invoice);
	}

	/**
	 * Make HTTP request to QuickBooks API
	 */
	private function makeRequest(string $method, string $endpoint, array $data = null): array {
		$url = $this->baseUrl . $endpoint;

		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: ' . $this->generateAuthHeader($method, $url)
		];

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_TIMEOUT        => 30
		]);

		if ($data && ($method === 'POST' || $method === 'PUT')) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if (curl_error($curl)) {
			throw new Exception('cURL Error: ' . curl_error($curl));
		}

		curl_close($curl);

		$decodedResponse = json_decode($response, true);

		if ($httpCode >= 400) {
			throw new Exception('QuickBooks API Error: ' . ($decodedResponse['Fault']['Error'][0]['Detail'] ?? 'Unknown error'));
		}

		return $decodedResponse;
	}

	/**
	 * Generate OAuth 1.0a authorization header
	 */
	private function generateAuthHeader(string $method, string $url): string {
		$timestamp = time();
		$nonce = bin2hex(random_bytes(16));

		$oauthParams = [
			'oauth_consumer_key'     => $this->consumerKey,
			'oauth_nonce'            => $nonce,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => $timestamp,
			'oauth_token'            => $this->accessToken,
			'oauth_version'          => '1.0'
		];

		// Create signature base string
		$paramString = http_build_query($oauthParams, '', '&', PHP_QUERY_RFC3986);
		$signatureBaseString = $method . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

		// Create signing key
		$signingKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($this->accessTokenSecret);

		// Generate signature
		$signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
		$oauthParams['oauth_signature'] = $signature;

		// Build authorization header
		$authHeader = 'OAuth ';
		$headerParams = [];
		foreach ($oauthParams as $key => $value) {
			$headerParams[] = $key . '="' . rawurlencode($value) . '"';
		}
		$authHeader .= implode(', ', $headerParams);

		return $authHeader;
	}
}

// Usage Example
try {
	$qb = new QuickBooksCRUD(
		'your_consumer_key',
		'your_consumer_secret',
		'your_access_token',
		'your_access_token_secret',
		'your_realm_id',
		true // sandbox mode
	);

	// Create a customer
	$newCustomer = $qb->createCustomer([
		'name'    => 'John Doe',
		'company' => 'Acme Corp',
		'email'   => 'john@acme.com',
		'phone'   => '555-1234',
		'address' => '123 Main St',
		'city'    => 'Charlotte',
		'state'   => 'NC',
		'zip'     => '28202'
	]);

	echo "Customer created: " . json_encode($newCustomer, JSON_PRETTY_PRINT) . "\n";

	// Get customer
	$customerId = $newCustomer['QueryResponse']['Customer'][0]['Id'];
	$customer = $qb->getCustomer($customerId);
	echo "Retrieved customer: " . json_encode($customer, JSON_PRETTY_PRINT) . "\n";

	// Update customer
	$updatedCustomer = $qb->updateCustomer($customerId, [
		'phone' => '555-5678'
	]);
	echo "Updated customer: " . json_encode($updatedCustomer, JSON_PRETTY_PRINT) . "\n";

	// Create an invoice
	$invoice = $qb->createInvoice([
		'customer_id' => $customerId,
		'line_items'  => [
			[
				'item_id'    => '1',
				'quantity'   => 2,
				'unit_price' => 50.00,
				'amount'     => 100.00
			]
		]
	]);
	echo "Invoice created: " . json_encode($invoice, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
	echo "Error: " . $e->getMessage() . "\n";
}

?>
