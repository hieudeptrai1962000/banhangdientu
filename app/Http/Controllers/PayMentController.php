<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Session;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

use PayPal\Api\ExecutePayment;

use PayPal\Api\PaymentExecution;


use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

use Cart,Auth,DateTime,DB;
use App\Oders;
use App\Oders_detail;
class PayMentController extends Controller
{    
    private $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                config('paypal.client_id'),
                config('paypal.secret')
            )
        );
        $this->apiContext->setConfig(config('paypal.settings'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $rq)
    {
        $payment_id = Session::get('payment_id');
        Session::forget('payment_id');

        $execution = new PaymentExecution();
        $execution->setPayerId($_GET['PayerID']);
        $payment = payment::get($payment_id,$this->apiContext);

        try {
            $result = $payment->execute($execution,$this->apiContext);
            if ($result->getState() =='approved') 
            {
                $oder = new Oders();
                $total =0;
                foreach (Cart::content() as $row) {
                    $total = $total + ( $row->qty * $row->price);
                }
                $oder->c_id = Auth::user()->id;
                $oder->qty = Cart::count();
                $oder->sub_total = floatval($total);
                $oder->total =  floatval($total);
                $oder->status = 1;
                $oder->type = 'paypal';
                $oder->note = $result->id;
                $oder->created_at = new datetime;
                $oder->save();
                $o_id =$oder->id;
                foreach (Cart::content() as $row) {
                   $detail = new Oders_detail();
                   $detail->pro_id = $row->id;
                   $detail->qty = $row->qty;
                   $detail->o_id = $o_id;
                   $detail->created_at = new datetime;
                   $detail->save();
                } 
            Cart::destroy();   
            return redirect()->route('getcart')
            ->with(['flash_level'=>'result_msg','flash_massage'=>'Thanh to??n ????n h??ng th??nh c??ng !']);     
            } else {
                return redirect()->route('getcart')
                ->with(['flash_level'=>'result_msg','flash_massage'=>' Thanh to??n th???t b???i !']);  
            }
        } catch (Exception $e) {
            return redirect()->route('getcart')
                ->with(['flash_level'=>'result_msg','flash_massage'=>'R???t ti???c ???? x???y ra l???i trong qu?? tr??nh thanh to??n !']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $setting = DB::table('settings')->first();
        // ### Payer
        // A resource representing a Payer that funds a payment
        // For paypal account payments, set payment method
        // to 'paypal'.
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        // // ### Itemized information
        // // (Optional) Lets you specify item wise
        // // information
        $subtotal = 0;
        $it=array();
        foreach(Cart::content() as $row) {  
            $item = new Item();
            $item->setName($row->name)
                ->setCurrency('USD')
                ->setQuantity($row->qty)
                ->setPrice(intval($row->price / ($setting->tigia)));
            array_push($it,$item);    
            $subtotal =intval($subtotal +($row->price *$row->qty) / ($setting->tigia));           
        }
        // $itemList = new ItemList();
        // $itemList->setItems(array($item2));

        $itemList = new ItemList();
        $itemList->setItems($it);
        // dd($itemList);
        // // ### Additional payment details
        // // Use this optional field to set additional
        // // payment information such as tax, shipping
        // charges etc.
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal(intval($subtotal));
        // ### Amount
        // Lets you specify a payment amount.
        // You can also specify additional details
        // such as shipping, tax.      
        $total =intval($subtotal + $details->tax + $details->shipping); 
        // dd($total.'--------'.$total);
        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($total)
            ->setDetails($details);

        // ### Transaction
        // A transaction defines the contract of a
        // payment - what is the payment for and who
        // is fulfilling it. 
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("????n h??ng c???a kh??ch h??ng : ".Auth::user()->name)
            ->setInvoiceNumber(uniqid());
        // dd($transaction->amount);
        // ### Redirect urls
        // Set the urls that the buyer must be redirected to after 
        // payment approval/ cancellation.
        // $baseUrl = getBaseUrl();
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('payment.create'))
            ->setCancelUrl(route('payment.create'));
        // ### Payment
        // A Payment Resource; create one using
        // the above types and intent set to 'sale'
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        // dd($total);
        // For Sample Purposes Only.
        // $request = clone $payment;
        // ### Create Payment
        // Create a payment by calling the 'create' method
        // passing it a valid apiContext.
        // (See bootstrap.php for more on `ApiContext`)
        // The return object contains the state and the
        // url to which the buyer must be redirected to
        // for payment approval
        try {
            $payment->create($this->apiContext);
        } catch (Exception $ex) {
            // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
            // ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
            echo 'that bai';
            exit(1);
        }
        // ### Get redirect url
        // The API response provides the url that you must redirect
        // the buyer to. Retrieve the url from the $payment->getApprovalLink()
        // method
        $approvalUrl = $payment->getApprovalLink();
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
         // ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
        // echo "<pre>";
        // return $payment;
        Session::put('payment_id', $payment->id);

        return redirect($approvalUrl);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
