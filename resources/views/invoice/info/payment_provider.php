<?php
declare(strict_types=1);
?>

<p><b>How do I setup an online payment provider eg. Mollie? (Developer: https://my.mollie.com) (Tutorial)</b>
</p>

<p><b>Step 1:</b> Add Keys to src/Invoice/Setting/SettingRepository <code>public function payment_gateways()</code> array e.g.
    <br>
    <br></p>
    
        <pre>
        'Mollie' => array( <br>
                'testOrLiveApiKey' => array( <br>
                    'type' => 'text', <br>
                    'label' => 'Test or Live Api Key i.e key starts with test_ or live_' <br>
                ), <br>
                'partnerID' => array( <br>
                    'type' => 'text', <br>
                    'label' => 'Partner ID' <br>
                ), <br>
                'profileID' => array( <br>
                    'type' => 'text', <br>
                    'label' => 'Profile ID' <br>
                ) <br>
            ) <br>
        </pre>    
   

<p><b>Step 2:</b> Goto Settings...View...Online Payment and actually select your Payment Provider from the dropdown.
   The array key value pairs that you entered will now be used to create either text boxes, password boxes, or
   checboxes depending on how you created your array in the first step.
</p>   
<p><b>Step 3:</b> Mollie, for instance, uses 4 keys that are unique to Payment Gateways and have never been used before
   i.e. liveApiKey, testApiKey, partnerID, and profileID. These require new translations 
   in C:\wamp64\www\invoice\resources\messages\en. 
</p>   
   <pre>
    // Mollie ClientAPI 15 March 2024
    'g.online_payment_testOrLiveApiKey' => 'Test or Live Api Key i.e starts with test_ or live_',
    'g.online_payment_partnerID' => 'Partner ID',
    'g.online_payment_profileID' => 'Profile ID',
   </pre>
   
<p><b>Step 4:</b> Insert the relevant data in. When entering Mollie's live API key in on the form it appears as a 
    password because you created the following array in Step 1. The text box's type is password. 
</p>
   <pre>
     'liveApiKey' => array(
                    'type' => 'password',
                    'label' => 'Live Api Key'
                ),  
   </pre>
<p><b>Step 5:</b>
    Add to the PaymentInformationController the required code. 
    Refer to the 4 tested gateways already setup.
</p>