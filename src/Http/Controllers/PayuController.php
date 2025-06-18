<?php


namespace Vfixtechnology\Payu\Http\Controllers;
use Illuminate\Http\Request;

use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Sales\Repositories\InvoiceRepository;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Controller;

class PayuController extends Controller
{

	/**
	 * OrderRepository $orderRepository
	 *
	 * @var \Webkul\Sales\Repositories\OrderRepository
	 */
	protected $orderRepository;
	/**
	 * InvoiceRepository $invoiceRepository
	 *
	 * @var \Webkul\Sales\Repositories\InvoiceRepository
	 */
	protected $invoiceRepository;

	/**
	 * Create a new controller instance.
	 *
	 *
	 * @return void
	 */
	public function __construct(OrderRepository $orderRepository,  InvoiceRepository $invoiceRepository)
	{
		$this->orderRepository = $orderRepository;
		$this->invoiceRepository = $invoiceRepository;
	}

	/**
	 * hash creation
	 */
	public function getHashKey($params)
	{
		return hash('sha512', $params['key'] . '|' . $params['txnid'] . '|' . $params['amount'] . '|' . $params['productinfo'] . '|' . $params['firstname'] . '|' . $params['email'] . '|' . $params['udf1'] . '|' . $params['udf2'] . '|' . $params['udf3'] . '|' . $params['udf4'] . '|' . $params['udf5'] . '||||||' . $params['salt']);
	}

	/**
	 * Redirects to the paytm server.
	 *
	 * @return \Illuminate\View\View
	 */

	public function redirect()
	{
		$cart = Cart::getCart();


		$billingAddress = $cart->billing_address;

		$MERCHANT_KEY = core()->getConfigData('sales.payment_methods.payu.payu_merchant_key');
		$SALT = core()->getConfigData('sales.payment_methods.payu.salt_key');

		if (core()->getConfigData('sales.payment_methods.payu.payu-website') == "Sandbox") :
			$PAYU_BASE_URL = "https://test.payu.in";        // For Sandbox Mode
		else :
			$PAYU_BASE_URL = "https://secure.payu.in";        // For Live Mode
		endif;

		$shipping_rate = $cart->selected_shipping_rate ? $cart->selected_shipping_rate->price : 0; // shipping rate
		$discount_amount = $cart->discount_amount; // discount amount
		$total_amount =  ($cart->sub_total + $cart->tax_total + $shipping_rate) - $discount_amount; // total amount

		$posted  = array(
			"key" => $MERCHANT_KEY,
			"txnid" => $cart->id . '_' . now()->format('YmdHis'),
			"amount" => $total_amount,
			"firstname" => $billingAddress->name,
			"email" => $billingAddress->email,
			"phone" => $billingAddress->phone,
			"surl" => route('payu.verify'),
			"furl" => route('payu.failure'),
			"curl" => "cancel",
			"hash" => '',
			"productinfo" => 'Bagisto Order no ' . $cart->id,
			'udf1' => '',
			'udf2' => '',
			'udf3' => '',
			'udf4' => '',
			'udf5' => '',
			'salt' => $SALT

		);
		/*** hash key generate  */
		$hash = $this->getHashKey($posted);
		$action = $PAYU_BASE_URL . '/_payment';
		return view('payu::payu-redirect')->with(compact('MERCHANT_KEY', 'SALT', 'action', 'posted', 'hash'));
	}

	/**
	 * success
	 */
    public function verify(Request $request)
    {
        try {
           // \Log::info('Payu Verify Incoming Request:', $request->all());

            $transactionId = $request->input('mihpayid');

            if (!$transactionId) {
                session()->flash('error', 'Payu payment verification failed: Missing transaction ID');
                return redirect()->route('shop.checkout.cart.index');
            }

            // Extract the cart ID from txnid (before the underscore)
            $txnid = $request->input('txnid');
            $cartId = explode('_', $txnid)[0] ?? null;

            if (!$cartId) {
                session()->flash('error', 'Cart ID not found in the transaction ID');
                return redirect()->route('shop.checkout.cart.index');
            }

            // 1. Try to get the current session cart
            $cart = \Webkul\Checkout\Facades\Cart::getCart();
           // \Log::info('Cart Details without session loss:', ['cart' => json_encode(\Webkul\Checkout\Facades\Cart::getCart())]);



            // 2. If cart not found, try getting from extracted cart ID
            if (!$cart) {
                $cart = app(\Webkul\Checkout\Repositories\CartRepository::class)->find($cartId);

                if ($cart && $cart->customer_id) {
                    $customer = app(\Webkul\Customer\Repositories\CustomerRepository::class)->find($cart->customer_id);
                    if ($customer) {
                        auth('customer')->login($customer);
                        \Log::info('Customer auto-logged in from cart ID: ' . $cartId);
                    }
                }

                // Set the cart in session
                if ($cart) {
                    \Webkul\Checkout\Facades\Cart::setCart($cart);
                }
            }

            // 3. Final cart check
            if (!$cart) {
                session()->flash('error', 'Cart not found. Please try again.');
                return redirect()->route('shop.checkout.cart.index');
            }

            // 4. Create order
            $data = (new OrderResource($cart))->jsonSerialize(); // Bagisto v2.2
            $order = $this->orderRepository->create($data);
            $this->orderRepository->update(['status' => 'processing'], $order->id);

            if ($order->canInvoice()) {
                $this->invoiceRepository->create($this->prepareInvoiceData($order));
            }

            \Webkul\Checkout\Facades\Cart::deActivateCart();

            session()->flash('order_id', $order->id);
            return redirect()->route('shop.checkout.onepage.success');

        } catch (\Exception $e) {
            \Log::error('Payu Verify Exception: ' . $e->getMessage());
            session()->flash('error', 'Something went wrong during payment verification.');
            return redirect()->route('shop.checkout.cart.index');
        }
    }



	/**
	 * failure
	 */
	public function failure()
	{
		session()->flash('error', 'Payu payment either cancelled or transaction failure.');
		return redirect()->route('shop.checkout.cart.index');
	}

	/**
	 * Prepares order's invoice data for creation.
	 *
	 * @return array
	 */
	protected function prepareInvoiceData($order)
	{
		$invoiceData = ["order_id" => $order->id,];

		foreach ($order->items as $item) {
			$invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
		}

		return $invoiceData;
	}
}
