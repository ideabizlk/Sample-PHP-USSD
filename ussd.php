<?php
/**
 * Created by IntelliJ IDEA.
 * User: Ideamart
 * Date: 2019-03-26
 * Time: 6:23 PM
 */
header('Content-Type: application/json');
$body = file_get_contents('php://input');
makeLog($body);
$ussd = json_decode($body);

//Config Callback or autodetect
$notifyURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

//Runtime Variables
$txn = new stdClass();
$txn->session = $ussd->inboundUSSDMessageRequest->sessionID;
$txn->msisdn = $ussd->inboundUSSDMessageRequest->address;
$txn->shortCode = $ussd->inboundUSSDMessageRequest->shortCode;
$txn->keyword = $ussd->inboundUSSDMessageRequest->keyword;
$txn->inboundUSSDMessage = $ussd->inboundUSSDMessageRequest->inboundUSSDMessage;


$txn_history = loadHistory($txn->session);

makeLog("IN : SESSION : " . $txn->session . " | MSISDN : " . $txn->msisdn . " | SC : " . $txn->shortCode . " | KW :" . $txn->keyword . " | MSG : " . $txn->inboundUSSDMessage);

$txn->res_message = "My Msg";
$txn->res_Action = "mtcont";

/**
 * Do your logic using session variables here and set bellow variables
 * $txn->res_message = "My Msg";
 * $txn->res_Action = "mtcont";
 */



makeLog("OUT : SESSION : " . $txn->session . " | MSISDN : " . $txn->msisdn . " | SC : " . $txn->shortCode . " | KW :" . $txn->keyword . " | MSG : " . $txn->res_message . " | ACTION : " . $txn->res_Action);
saveSession($txn);

$out = array(
    "outboundUSSDMessageRequest" => array(
        "address" => $txn->msisdn,
        "keyword" => $txn->keyword,
        "shortCode" => $txn->shortCode,
        "outboundUSSDMessage" => $txn->res_message,
        "clientCorrelator" => round(microtime(true) * 1000),
        "ussdAction" => $txn->res_Action,
        "responseRequest" => array("notifyURL" => $notifyURL, "callbackData" => round(microtime(true) * 1000))
    )

);

makeLog(json_encode($out));
echo json_encode($out);


/*
 * Functions
 */
function makeLog($line)
{
    file_put_contents('Log_' . date("Y-m-d") . '.txt', date("Y-m-d H:i:s") . "-" . $line . PHP_EOL, FILE_APPEND);
}

function saveSession($txn)
{
    //Can save on DB if required

    //saving to Session
    if (!isset($_SESSION['txns']))
        $_SESSION['txns'] = array();

    $_SESSION['txns'][] = $txn;

    //Saving to file
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }

    file_put_contents("logs/" . $txn->session, json_encode($_SESSION['txns']));

}

function loadHistory($session)
{

    //Can load session from DB if required
    session_id($session);
    session_start();

    if (isset($_SESSION['txns']))
        return $_SESSION['txns'];

    return null;
}