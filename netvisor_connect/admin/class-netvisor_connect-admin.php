<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       -
 * @since      1.0.0
 *
 * @package    Netvisor_connect
 * @subpackage Netvisor_connect/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Netvisor_connect
 * @subpackage Netvisor_connect/admin
 * @author     Mika Kuittinen <Mika4131@gmail.com>
 */
class Netvisor_connect_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	//takes xml file that is assumed in the correct form and has all the necessary information in the correct format

	public function getCustomerIdentifier($order)
	{
		$url = 'https://isvapi.netvisor.fi/customerlist.nv';
		$organisationIdentifier = '';

		$customerXML = apply_filters('getFromNet', $url, $callParameters);
		$customerKey = false;
		$user = new WP_user($order->get_user_id());
		$callParameters = $user->tumblr; //should be y-number, but could also be the Netvisor key of customer. In that case change the strcmp $customer->OrganisationIdentifier => $customer->Netvisorkey
		//seach with organisation identifier
		foreach ($customerXML->Customerlist->Customer as $customer) {
			if (strcmp($customer->OrganisationIdentifier, $callParameters) == 0) {
				$customerKey = $customer->Code;
				return (string)$customerKey;
			}
		}

		$callParameters = $user->first_name . ' ' . $user->last_name;
		//this searches the customerXML for a customer with a name that matches
		foreach ($customerXML->Customerlist->Customer as $customer) {
			if (strcmp($customer->Name, $callParameters) == 0) {
				$customerKey = $customer->Code;
				return (string)$customerKey;
			}
		}

		//if customer is not found than an erro is sent
		if ($customerkey == false) {
			//could add function to make a customer from the data given here
			$log = new WC_Logger();
			$log_entry = " Error type: No Customer by these credentials\n Name:" . $callParameters . "\n Y-number" . $user->tumblr . "\n Sent to Url:" . $url . "\n Order: " . $order;
			$log->info($log_entry, array('source' => 'Failed Nevisor Contacts'));
			return;
		}
	}

	public function sendBillToNetvisor($order_id, $posted_data, $order)
	{
		if ($order->get_payment_method_title() != 'Order by invoice') { //the send will only trigger if the payment method is by invoice, which should be available on to certain customers
			return;
		}
		$url = 'https://isvapi.netvisor.fi/salesinvoice.nv?method=add';
		$order_id = $order->get_id();
		$order_data = $order->get_data();
		//datehelper is used for getting the data of the event, for use in the XML
		$datehelper = $order->order_date;
		$salesdate = date("Y-m-d", strtotime($datehelper));
		$customer = new WC_Customer($order->get_user_id());
		$fullxml = '<root><salesinvoice>';
		$fullxml = $fullxml . '<salesinvoicedate>' . $salesdate . '</salesinvoicedate>';
		$fullxml = $fullxml . '<salesinvoiceeventdate>' . $salesdate . '</salesinvoiceeventdate>';
		$fullxml = $fullxml . '<salesinvoiceduedate>' . gmdate('Y-m-d', date(strtotime($salesdate)) + 604800 * 3) . '</salesinvoiceduedate>'; // due date is three weeks from the date of the event, Might be changed
		$fullxml = $fullxml . '<salesinvoicedeliverydate>' . gmdate('Y-m-d', date(strtotime($salesdate)) + 604800) . '</salesinvoicedeliverydate>'; // deliverydate is a week from now. Might be changed
		$fullxml = $fullxml . '<salesinvoicedeliverytocustomerdate format=' . '"ansi"' . ' type=' . '"weeknumber"' . '>' . str_replace(":", "W", gmdate('Y-:W', date(strtotime($salesdate)) + 604800 * 4)) . '</salesinvoicedeliverytocustomerdate>';
		$fullxml = $fullxml . '<salesinvoiceamount>' . $order->get_total() . '</salesinvoiceamount>';

		$fullxml = $fullxml . '<invoicetype>order</invoicetype>';
		$fullxml = $fullxml . '<salesinvoicestatus type="netvisor">undelivered</salesinvoicestatus>';
		$fullxml = $fullxml . '<invoicingcustomeridentifier type=' . "'customer'" . '>' . $this->getCustomerIdentifier($order) . '</invoicingcustomeridentifier>';
		$fullxml = $fullxml . '<invoicingcustomername>' . $customer->get_first_name() . '</invoicingcustomername>';
		$fullxml = $fullxml . '<invoicingcustomernameextension>' . $customer->get_last_name() . '</invoicingcustomernameextension>';
		$fullxml = $fullxml . '<invoicingcustomeraddressline>' . $customer->get_billing_address_1() . '</invoicingcustomeraddressline>';
		$fullxml = $fullxml . '<invoicingcustomerpostnumber>' . $customer->get_billing_postcode() . '</invoicingcustomerpostnumber>';
		$fullxml = $fullxml . '<invoicingcustomertown>' . $customer->get_billing_city() . '</invoicingcustomertown>';
		$fullxml = $fullxml . '<invoicingcustomercountrycode type=' . "'ISO-3166'" . '>' . $customer->get_billing_country() . '</invoicingcustomercountrycode>';

		$fullxml = $fullxml . '<deliveryaddressname>' . $order_data['shipping']['first_name'] . ' ' . $order_data['shipping']['last_name'] . '</deliveryaddressname>';
		$fullxml = $fullxml . '<deliveryaddressline>' . $order_data['shipping']['address_1'] . '</deliveryaddressline>';
		$fullxml = $fullxml . '<deliveryaddresspostnumber>' . $order_data['shipping']['postcode'] . '</deliveryaddresspostnumber>';
		$fullxml = $fullxml . '<deliveryaddresstown>' . $order_data['shipping']['city'] . '</deliveryaddresstown>';
		$fullxml = $fullxml . '<deliveryaddresscountrycode type="ISO-3316">FI</deliveryaddresscountrycode>'; //set to FI, but it may have to be turned into code that checks the country of delivery

		$fullxml = $fullxml . '<salesinvoicetaxhandlingtype>countrygroup</salesinvoicetaxhandlingtype>';
		$fullxml = $fullxml . '<ordernumber>' . $order->order_number . '</ordernumber>';
		$fullxml = $fullxml . '<invoicelines>';

		//goes through order items to add into the XML
		foreach ($order->get_items() as $item_key => $item) {
			if (!empty($item)) {

				$fullxml = $fullxml . '<invoiceline><salesinvoiceproductline>';
				$fullxml = $fullxml . '<productidentifier type=' . "'customer'" . '>WC' . $item->get_product_id() . '</productidentifier>';
				$fullxml = $fullxml . '<productname>' . $item->get_name() . '</productname>';
				$fullxml = $fullxml . '<productunitprice type=' . "'net'" . '>' . $item->get_subtotal() . '</productunitprice>';
				$fullxml = $fullxml . '<productvatpercentage vatcode=' . "'KOMY'" . '>' . round(($item->get_subtotal_tax() / $item->get_subtotal()) * 100, 1) . '</productvatpercentage>';
				$fullxml = $fullxml . '<salesinvoiceproductlinequantity>' . $item->get_quantity() . '</salesinvoiceproductlinequantity>';
				$fullxml = $fullxml . '<accountingaccountsuggestion>' . 3000 . '</accountingaccountsuggestion>'; //account suggestion. Might have to be changed
				$fullxml = $fullxml . '</salesinvoiceproductline></invoiceline>';
			}
		}
		$fullxml = $fullxml . '</invoicelines>';
		$fullxml = $fullxml . '</salesinvoice></root>';
		do_action('sendToNet', utf8_encode($fullxml), $url, $order);
	}


	public function sendToNetvisor($xml, $url, $extraData)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		//gets headers from another function that are than used for authentication to Netvisor
		$headers = apply_filters('getHeaders', $url);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		//throws exception if given url is not correct. this error occurs usually if the given url was wrong or is no longer active
		if (curl_errno($curl)) {
			throw new Exception(curl_error($curl));
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		//debug things not really used unless needed
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$resp = curl_exec($curl);
		$respXML = simplexml_load_string($resp);
		//if sending of data fails for what ever reason it throws an expetion(? it may be able to be removed) and ads information to the log
		if ($respXML->ResponseStatus->Status == "FAILED") {
			//var_dump($resp);
			if ($extraData->post_type != '' && $extraData->post_type != 'product') {
				$extraData->add_order_note("could Not send order to Netvisor: Check Error log");
			}
			$log = new WC_Logger();
			$log_entry = " Error type: Post\n Sent to Url:" . $url . "\n sent xml: " . $xml . "\n receiver:" . $resp;
			$log->info($log_entry, array('source' => 'Failed Nevisor Contacts'));
		}
		curl_close($curl);
	}

	//checks if the product already exists in the Netvisor account. Used for choosing between methdod in the onProductChange function
	function checkingNetvisorForProduct($productID)
	{
		$url = 'https://isvapi.netvisor.fi/productlist.nv';
		$productXML = apply_filters('getFromNet', $url, '');
		$productsituation;
		$productID = 'WC' . $productID;
		$productsituation = false;
		//checks through the list of products to see if the product code matches the given product ID
		foreach ($productXML->ProductList->Product as $product) {
			if (strcmp($product->ProductCode, $productID) == 0) {
				$productsituation = (string)$product->NetvisorKey;
				break;
			}
		}


		return $productsituation;
	}

	function getStock($product)
	{
		if (!$product->managing_stock() && !$product->is_in_stock()) {
			return '0';
		}
		return '1';
	}

	//gets the product account from its attributes
	function getProductAccount($product, $attribute)
	{
		if ($product->get_attribute($attribute) == '') {
			return 3000;
		} else {
			return (int)($product->get_attribute($attribute));
		}
	}

	function getProductGroup($product, $attribute)
	{
		if ($product->get_attribute($attribute) == '') {
			return 'TBD';
		} else {
			return ($product->get_attribute($attribute));
		}
	}

	function getCountryOfOrigin($product, $attribute)
	{
		if ($product->get_attribute($attribute) == '') {
			return '';
		} else {
			return ($product->get_attribute($attribute));
		}
	}

	//used to add product into the Netvisor database.
	public function onProductChange($product_id)
	{
		$product = wc_get_product($product_id);
		$trueProductID = $this->checkingNetvisorForProduct($product_id);
		if ($trueProductID == false) {
			$url = 'https://isvapi.netvisor.fi/product.nv?method=add';
		} else {
			$url = 'https://isvapi.netvisor.fi/product.nv?method=edit&id=' . $trueProductID;
		}
		$fullxml = '<root><product><productbaseinformation>';
		$fullxml = $fullxml . '<productcode>WC' . $product_id . '</productcode>';
		$fullxml = $fullxml . '<productgroup>' . $this->getProductGroup($product, 'productGroup') . '</productgroup>';
		$fullxml = $fullxml . '<name>' . $product->get_name() . '</name>';
		$fullxml = $fullxml . '<description>' . $product->get_short_description() . '</description>';
		$fullxml = $fullxml . '<unitprice type=' . "'net'" . '>' . $product->get_price() . '</unitprice>';
		$fullxml = $fullxml . '<unit>' . $product->get_sku() . '</unit>';
		//$fullxml=$fullxml.'<unitweight></unitweigth>';
		//$fullxml=$fullxml.'<purchaseprice></purchaseprice>';//tariffheading; comissionpercentage are missing
		$fullxml = $fullxml . '<isactive>' . 1 . '</isactive>';
		$fullxml = $fullxml . '<issalesproduct>' . 1 . '</issalesproduct>'; //1 is yes, 2 is no
		$fullxml = $fullxml . '<inventoryenabled>' . $this->getStock($product) . '</inventoryenabled>';
		$fullxml = $fullxml . '<countryoforigin type=' . "'ISO-3166'" . '>' . $this->getCountryOfOrigin($product, 'countryOrigin') . '</countryoforigin>';
		$fullxml = $fullxml . '</productbaseinformation><productbookkeepingdetails>';
		$fullxml = $fullxml . '<defaultvatpercentage>' . 0 . '</defaultvatpercentage>';
		$fullxml = $fullxml . '<defaultdomesticaccountnumber>' . $this->getProductAccount($product, 'domesticA') . '</defaultdomesticaccountnumber>'; //might want to change this for what works best for the product as well as the foolowing account numbers
		$fullxml = $fullxml . '<defaulteuaccountnumber>' . $this->getProductAccount($product, 'EUA') . '</defaulteuaccountnumber>';
		$fullxml = $fullxml . '<defaultoutsideeuaccountnumber>' . $this->getProductAccount($product, 'outsideA') . '</defaultoutsideeuaccountnumber>';
		$fullxml = $fullxml . '</productbookkeepingdetails>';
		$fullxml = $fullxml . '<productadditionalinformation>';
		if ($product->get_weight() != '') {
			$fullxml = $fullxml . '<productnetweight>' . $product->get_weight() . '</productnetweight>';
		}
		if ($product->get_weight() != '') {
			$fullxml = $fullxml . '<productgrossweight>' . $product->get_weight() . '</productgrossweight>';
		}
		//add packaging if needed
		$fullxml = $fullxml . '<productweightunit>kg</productweightunit>';
		if ($product->get_width() != '') {
			$fullxml = $fullxml . '<productpackageinformation>';
			if ($product->get_width() != '') {
				$fullxml = $fullxml . '<packagewidth>' . $product->get_width() . '</packagewidth>';
			}
			if ($product->get_height() != '') {
				$fullxml = $fullxml . '<packageheight>' . $product->get_height() . '</packageheight>';
			}
			if ($product->get_length() != '') {
				$fullxml = $fullxml . '<packagelength>' . $product->get_length() . '</packagelength>';
			}
			$fullxml = $fullxml . '</productpackageinformation>';
		}
		$fullxml = $fullxml . '</productadditionalinformation>';
		$fullxml = $fullxml . '</product></root>';

		do_action('sendToNet', utf8_encode($fullxml), $url, $product);
	}





	//looks if the customers exists in the Netvisor, if not than a new one is a made rather then old one edited
	function checkingNetvisorForCustomer($user_id)
	{
		$url = 'https://isvapi.netvisor.fi/customerlist.nv';
		$customerXML = apply_filters('getFromNet', $url, '');
		//checks through the list of products to see if the product code matches the given product ID
		$callParameters = (new WP_user($user_id))->tumblr; //should be y-number, but could also be the Netvisor key of customer. In that case change the strcmp $customer->OrganisationIdentifier => $customer->Netvisorkey
		//this searches the customerXML for a customer with a name that matches
		foreach ($customerXML->Customerlist->Customer as $customer) {
			if (strcmp($customer->OrganisationIdentifier, $callParameters) == 0) {
				$customerKey = $customer->Netvisorkey;
				return (string)$customerKey;
			}
		}
		return false;
	}




	//do_action( 'user_register', $user_id )

	public function registerUser($user_id)
	{
		$trueUserID = $this->checkingNetvisorForCustomer($user_id);
		$customerCode = '';
		$fullxml = '<root><customer><customerbaseinformation>';
		if ($trueUserID == false) {
			$url = 'https://isvapi.netvisor.fi/customer.nv?method=add';
			//$customerCode=$user->first_name.$user[0]->last_name[0].rand(0,1000);//when adding a customer the customerCode will be formed from the name of the customer and a random integer between 0-1000
			$fullxml = $fullxml . '<internalidentifier type=' . "'automatic'" . '></internalidentifier>'; // makes new identifier automatically
		} else {
			$url = 'https://isvapi.netvisor.fi/customer.nv?method=edit&id=' . $trueUserID;
		}
		$this->onCustomerChange($user_id, $url, $fullxml);
	}

	//used to remove $old_user_data from the hook data
	public function updateUser($user_id, $old_user_data)
	{
		//check for if the user is customer
		$user_info = get_userdata($user_id);
		if (in_array('customer', $user_info->roles) == false) {
			return;
		}
		$this->registerUser($user_id);
	}

	//used to add customer to Nevisor
	public function onCustomerChange($user_id, $url, $fullxml)
	{
		//$customer = get_user_by('id', $user_id);
		$customer = new WP_user($user_id);
		$fullxml = $fullxml . '<externalidentifier>' . $customer->tumblr . '</externalidentifier>';
		$fullxml = $fullxml . '<name>' . $customer->first_name . ' ' . $customer->last_name . '</name>';
		$fullxml = $fullxml . '<streetaddress>' . $customer->billing_address_1 . '</streetaddress>';
		$fullxml = $fullxml . '<city>' . $customer->billing_city . '</city>';
		$fullxml = $fullxml . '<postnumber>' . $customer->billing_postcode . '</postnumber>';
		$fullxml = $fullxml . '<country type=' . "'ISO-3166'" . '>' . $customer->billing_country . '</country>';
		$fullxml = $fullxml . '<phonenumber>' . $customer->billing_phone . '</phonenumber>';
		$fullxml = $fullxml . '<email>' . $customer->email . '</email>';
		$fullxml = $fullxml . '<isactive>' . 1 . '</isactive>';
		$fullxml = $fullxml . '<isprivatecustomer>' . 0 . '</isprivatecustomer>'; //gould check for y-number to determine
		$fullxml = $fullxml . '<emailinvoicingaddress>' . $customer->billing_email . '</emailinvoicingaddress>'; //billing email
		$fullxml = $fullxml . '</customerbaseinformation>';

		/* thought that delivery information is always given separetly though this can be changed to suit the needs of the Co.
		$fullxml=$fullxml.'<customerdeliverydetails><deliveryname>'.''.'</deliveryname>';
		$fullxml=$fullxml.'<deliverystreetaddress>'.''.'</deliverystreetaddress>';
		$fullxml=$fullxml.'<deliverycity>'.''.'</deliverycity>';
		$fullxml=$fullxml.'<deliverypostnumber>'.''.'</deliverypostnumber>';
		$fullxml=$fullxml.'<deliverycountry type='."ISO-3166".'>'.''.'</deliverycountry></customerdeliverydetails>';*/
		$fullxml = $fullxml . '</customer></root>';
		do_action('sendToNet', utf8_encode($fullxml), $url, $customer);
	}



	//filters
	//gets data from Netvisor based on the url given; ! note that the API must be open from netvisors side
	public function getFromNetvisor($url, $callParameters)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		//$headers = getAuthenticationH( $url);
		$headers = apply_filters('getHeaders', $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		if (curl_errno($curl)) {
			throw new Exception(curl_error($curl));
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, $callParameters);
		//debug things
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$resp = curl_exec($curl);
		$respXML = simplexml_load_string($resp);
		if ($respXML->ResponseStatus->Status == "FAILED") {
			$log = new WC_Logger();
			$log_entry = " Error type: GET\n Sent to Url:" . $url . "\n receiver:" . $resp;
			$log->info($log_entry, array('source' => 'Failed Nevisor Contacts'));
			//throw new Exception('Error has occured. Please try again later');
		}
		return $respXML;
	}

	//heres the needed authentication information that is required to start a connection with Netvisor. 
	public function getAuthenticationH($url)
	{
		$sender = ''; //name of the place the the connection is coming from
		$userKey = ''; // gotten from the API meant for these connection in Netvisor
		$partnerID = ''; //gotten from Netvisor team
		$timestamp = date('Y-m-d h:m:s.u'); //just general time of sending
		$language = 'FI'; // default, but can be ENG also
		$orgID = ''; // corporations identification number.
		$transactionID = 'TRANSID' . random_int(0, 999999999); //$order->get_transaction_id();

		$privateKey = ''; //number from NEtvisor
		$partnerPrivateKey = ''; //gotten from Netvisor when the account was gotten
		$parameters = array(
			$url,
			$sender,
			$userKey,
			$timestamp,
			$language,
			$orgID,
			$transactionID,
			$privateKey,
			$partnerPrivateKey
		);
		$macAdd = hash('sha256', (implode('&', $parameters)));

		return  array(
			"Content-Type: application/xml",
			"Accept: application/xml",
			'X-Netvisor-Authentication-Sender: ' . $sender,
			'X-Netvisor-Authentication-CustomerId: ' . $userKey,
			'X-Netvisor-Authentication-PartnerId: ' . $partnerID,
			'X-Netvisor-Authentication-Timestamp: ' . $timestamp,
			'X-Netvisor-Interface-Language: ' . $language,
			'X-Netvisor-Organisation-ID: ' . $orgID,
			'X-Netvisor-Authentication-TransactionId: ' . $transactionID, //
			'X-Netvisor-Authentication-MAC: ' . $macAdd,
			'X-Netvisor-Authentication-MACHashCalculationAlgorithm: SHA256'
		);
	}
}
