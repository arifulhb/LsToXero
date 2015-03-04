<?php


namespace AppioLab\LsToXero;

class LsToXero {

    /**
     *
     *
     *
     */
    public function prepareXeroInvoice($merchent,
                                     $accounts,
                                     $times,
                                     $paymentGroup)
    {


        $mrcnt_xero_revenew_code_id = $merchent['mrcnt_xero_revenew_code_id'];
        $mrcnt_xero_tc_id           = $merchent['mrcnt_xero_tc_id'];
        $mrcnt_xero_tc_name         = $merchent['mrcnt_xero_tc_name'];
        $mrcnt_xero_tc_group_name   = $merchent['mrcnt_xero_tc_group_name'];
		$mrcnt_xero_lineAmount 		= $merchent['mrcnt_xero_lineAmount'];


        $tran['details'] ='';


        $invoices="<Invoices>";
        $invoices.="<Invoice>";
//                TYPE
//                 ACCPAY (A bill – commonly known as a Accounts Payable or supplier invoice),
//                 ACCREC (A sales invoice – commonly known as an Accounts Receivable or customer invoice)
        $invoices.="<Type>ACCREC</Type>";

//                Status [DRAFT, SUBMITTED, DELETED, AUTHORISED, PAID, VOIDED]

        $invoices.="<Status>AUTHORISED</Status>";// ** NEED TO MAKE STATUS "AUTHORISED" FOR LIVE
        $invoices.="<Contact>";
        $invoices.="<Name>".$merchent['mrcnt_name']."</Name>";
        $invoices.="</Contact>";
        $invoices.="<Date>".$this->convertMyDate(date('d-m-Y',$times['startTime'] ))."T00:00:00</Date>";
        $invoices.="<DueDate>".$this->convertMyDate(date('d-m-Y',$times['startTime'] ))."T00:00:00</DueDate>";

        $invoices.="<Reference>Daily Sales: Auto</Reference>";

//                LineAmount Types [Exclusive, Inclusive, NoTax]


        $invoices.="<LineAmountTypes>".$mrcnt_xero_lineAmount."</LineAmountTypes>";
        $invoices.="<LineItems>";


        $_total = 0;
        $lines = "";
//            prepare transaction details
        foreach($accounts as $account){

            foreach($paymentGroup as $row){

//                    REMOVED 2014-09-12
//                    if($row['paymentTypeId']==$account['pos_payment_type_oid']){

//                        $tran['details'].=$account['pos_payment_type_name'].' '.$row['amount'].'<br>';
//                        $_total+=$row['amount'];
//
//                    }//end if

//                    echo 'acc link type: '.$account['acc_link_type'].' | pos_payment_type_oid : '.$account['pos_payment_type_oid'].' | ROW ID: '.$row['paymentTypeId'].' |  AMOUNT: '.$row['amount'].PHP_EOL;

                if($account['acc_link_type']==1 &&
                    $row['paymentTypeId']==$account['pos_payment_type_oid'] &&
                    $row['amount']>0  ){
//                            IF $row is Payment Type
                    $tran['details'].=$account['pos_payment_type_name'].' '.$row['amount'].'<br>';
                    $_total+=$row['amount'];

//                            echo 'NAME: '.$account['pos_payment_type_name'].' | Sub Total: '.$row['amount']." | Total : ".$_total.PHP_EOL;

                } else if($account['acc_link_type'] == 2 &&
                    $row['paymentTypeId']==$account['pos_payment_type_oid']){

//                      IF Row is Invoice Item Type
//                      INVENTORY ITEM
                    $lines.="<LineItem>";
                    $lines.='<Description>'.$account['xero_account_name'].'</Description>';
                    $lines.="<Quantity>1</Quantity>";
                    $unit_amount= (-1 * $row['amount']);
                    $lines.="<UnitAmount>".$unit_amount."</UnitAmount>";

                    $lines.='<LineAmount>'.$unit_amount.'</LineAmount>';

//                    $lines.="<AccountCode>".$acc['mrcnt_xero_revenew_code_id'].'</AccountCode>';
//                    $lines.="<AccountCode>".$acc['xero_account_id'].'</AccountCode>';
                    $lines.="<AccountCode>".$account['xero_code_id'].'</AccountCode>';
                    $lines.="</LineItem>";

                    unset($unit_amount);
                }

            }//end inner for loop

        }//END WHILE $accounts

//           New Line details
        $item="<LineItem>";
        $item.='<Description>'.$merchent['mrcnt_name'].' Sales</Description>';
        $item.="<Quantity>1</Quantity>";
        $item.="<UnitAmount>".$_total."</UnitAmount>";
//            $item.='<TaxType>NONE</TaxType>';
        $item.="<AccountCode>".$mrcnt_xero_revenew_code_id.'</AccountCode>';

//            IF TrackingCategory is selected, than add the TrackingCategory
        if(strlen($mrcnt_xero_tc_id)>1 ){

            $item.='<Tracking>';
            $item.='<TrackingCategory>';
            $item.='<TrackingCategoryID>'.$mrcnt_xero_tc_id.'</TrackingCategoryID>';
            $item.='<Name>'.$mrcnt_xero_tc_group_name.'</Name>';
            $item.='<Option>'.$mrcnt_xero_tc_name.'</Option>';
            $item.='</TrackingCategory>';
            $item.='</Tracking>';

        }//end TC

        $item.="</LineItem>";

//          Add $lines to $item
        if(isset($lines)){
            $item.=$lines;
        }





//            Add $item to $invoices
        $invoices.=$item;


        $invoices.="</LineItems>";
        $invoices.="</Invoice>";
        $invoices.="</Invoices>";

//        $xero = new Xero($key);
//        $result_invoices = $xero->save($invoices,'invoice');
        //POST TO XERO
//        $xero = new Xero($key);

//        $result_invoices = save_to_xero($invoices,'invoice',$key);

		$result = Array("invoice" => $invoices, "details"=>$tran['details']) ;
        return $result;

    }//end function



    public function prepareXeroPayment($invoiceId, $accounts, $times, $paymentGroup)
    {


        $paymentXml='<Payments>';
        foreach($accounts as $account){

            foreach($paymentGroup as $row) {

//                      if acc_link_type = 1 then
                if ($account['acc_link_type'] == 1 &&
                    $row['paymentTypeId'] == $account['pos_payment_type_oid'] &&
                    $row['amount']>0  ) {

                    $paymentXml .= '<Payment>';
                    $paymentXml .= '<Invoice><InvoiceID>' . $invoiceId . '</InvoiceID></Invoice>';
                    $paymentXml .= '<Account><Code>' . $account['xero_code_id'] . '</Code></Account>';
                    $paymentXml .= '<Date>' . convertMyDate(date('d-m-Y', $times['startTime'])) . 'T00:00:00</Date>'; //UPDATE DATE
                    $paymentXml .= '<Reference>' . $account['mrcnt_name'] . ' - ' . $account['pos_payment_type_name'] . ': ' . $account['pos_payment_type_oid'] . '</Reference>';
                    $paymentXml .= '<Amount>' . $row['amount'] . '</Amount>';
                    $paymentXml .= '</Payment>';
                }

            }//end foreach

        }//end while

        $paymentXml.='</Payments>';


        //SAVE A PAYMENT TO XERO
//        $PaymentResult = save_to_xero($paymentXml,'payment',$key);


//        $xero = new Xero($key);
//        $PaymentResult = $xero->save($paymentXml,'payment');

        return $paymentXml;

    }

    /**
     * List of Lightspeed Payment Types with total amount from a result
     *
     * @param Array $result Lightspeed result array
     *
     * @return array
     *
     *
     */
    public function getLSPaymentGroupResult($result)
    {

        $payments = $this->lsPaymentList($result);
//
//		return $payments;
//		exit();

        $groups = array();
        $key = 0;


        foreach ($payments as $item) {

//			@TODO update payment typeId
            $key = $item['paymentTypeId'];
//            $key = $item['paymentTypeTypeId'];

            if (!array_key_exists($key, $groups)) {

				$groups[$key] = array(
						'paymentTypeId' => $item['paymentTypeId'],
						'paymentTypeTypeId' => $item['paymentTypeTypeId'],
						'type' 				=> $item['type'],
						'amount' 			=> $item['amount']
				);

                /*$groups[$key] = array(
                    'paymentTypeId' => $item['paymentTypeId'],
                    'type' => $item['type'],
                    'amount' => $item['amount']
                );*/


            } else {
                $groups[$key]['amount'] = $groups[$key]['amount'] + $item['amount'];
                //$groups[$key]['itemMaxPoint'] = $groups[$key]['itemMaxPoint'] + $item['itemMaxPoint'];
            }
            $key++;
        }

		if(isset($groups['0'])){

			$quickCash = $groups['0']; //Get the Quick Cash

			unset($groups['0']);

			$result 	= Array();

			foreach($groups as $group){

				if($group['paymentTypeTypeId'] == 1){

//					Add quick cash to cash
					$ar['paymentTypeId'] 		= $group['paymentTypeId'];
					$ar['paymentTypeTypeId'] 	= $group['paymentTypeTypeId'];
					$ar['type'] 				= $group['type'];
					$ar['amount'] 				= $group['amount'] + $quickCash['amount'];

					array_push($result, $ar);

				}
				else{

					if($group['paymentTypeTypeId']!=2){
						array_push($result, $group);
					}

				}
			}//end foreach
			return $result;

		}//end if
		else{
			return $groups;
		}//end else



    }//end function


	/**
	 *
	 * @param $results
	 *
	 * @return array
	 *
	 */
    private  function lsPaymentList($results){

        $payments= Array();
        foreach($results as $item){

//          @TODO change paymentTypeId to paymentTypeTypeID
            /*$payment=array(	'paymentTypeId'	=>	$item->payment->paymentTypeId,
                			'type'			=>	$item->payment->type,
                			'amount'		=>	$item->payment->amount);*/

			/*$payment=array(	'paymentTypeTypeId'	=>	$item->payment->paymentTypeTypeId,
							'paymentTypeId'		=>	$item->payment->paymentTypeId,
							'type'				=>	$item->payment->type,
							'amount'			=>	$item->payment->amount);

            array_push($payments,$payment);*/

			if(count($item->payments)>0){

				if(count($item->payments)>1){


					foreach($item->payments as $payment){


						$payment_array = array(
							'paymentTypeTypeId'	=>	$item->payment->paymentTypeTypeId,
							'paymentTypeId'		=>	$payment->paymentTypeId,
							'type'				=>	$payment->type,
							'amount'			=>	$payment->amount);

						array_push($payments,$payment_array);
					}


				}else{


					$payment=array(
						'paymentTypeTypeId'	=>	$item->payment->paymentTypeTypeId,
						'paymentTypeId'		=>	$item->payments[0]->paymentTypeId,
						'type'				=>	$item->payments[0]->type,
						'amount'			=>	$item->payments[0]->amount);

					array_push($payments,$payment);

				}

			}



		}//end foreach

        return $payments;

    }//end function

    public  function convertMyDate($date){
        //CONVERT FROM dd-mm-yyyy
        //conver to Y M D
        $showDate='';
        //echo 'date: '.$date.'<br>';
        if($date!=''){
            $_year  =substr($date,6,4);
            $_month =substr($date,3,2);
            $_day =substr($date,0,2);

            $showDate=$_year.'-'.$_month.'-'.$_day;
        }
        return $showDate;
    }//end function


}//end class