<?php

session_start();
$sessid = session_id();

$mongo = new Mongo("localhost:27017");

// Set to something below your web server timeout.
$timeout = 3;

function getTime() {
    return date("Y-m-d H:i:s") . microtime();
}

function toHtmlId($mongoId) {
    return "wt" . $mongoId;
}

function toMongoId($htmlId) {
    return ltrim($htmlId, "wt");
}

if ($_GET['a'] === "load") {

    $items = $mongo->white->items;
    $data = array("list"=>$_GET['list']);
    $mr = $items->find($data)->sort(array("timestamp" => -1));
    $items = array();
    while ($mr->hasNext()) {
        $item = $mr->getNext();
        $items[] = array("id"=>toHtmlId($item['_id']->{'$id'}), 
                "text"=>$item['text'], "strike"=>$item['strike']);
    }
    print(json_encode(array("status"=>"ok", 
            "msg"=>"All items returned successfully.", "items"=>$items)));

} else if ($_GET['a'] === "save") {

    $id = $_GET['id'];
    $text = $_GET['text'];
    $items = $mongo->white->items;
    $item = array("text"=>$_GET['text']);
    if ($id === null || $id === "null") {
        $data = array("text"=>$_GET['text'], "strike"=>false, 
                "list"=>$_GET['list'], "timestamp"=>getTime());
        $items->insert($data);
        $id = toHtmlId($data['_id']->{'$id'});
    } else {
        $mongoId = new MongoID(toMongoId($id));
        $items->update(array("_id"=>$mongoId), array('$set'=>array("text"=>$_GET['text'])));
    }

    //file_put_contents($pollFile, toMongoId($id) . ":save\n", FILE_APPEND, LOCK_EX);

    print(json_encode(array("status"=>"ok", "msg"=>"Saved item.", "id"=>$id)));

} else if ($_GET['a'] === "delete") {

    $id = $_GET['id'];
    $items = $mongo->white->items;
    $mongoId = new MongoID(toMongoId($id));
    $items->remove(array("_id"=>$mongoId));

    //file_put_contents($pollFile, toMongoId($id) . ":delete\n", FILE_APPEND, LOCK_EX);

    print(json_encode(array("status"=>"ok", "msg"=>"Deleted item.")));

} else if ($_GET['a'] === "strike") {

    $id = $_GET['id'];
    $items = $mongo->white->items;
    $strike = $_GET['strike'] === true || $_GET['strike'] === "true" ? true : false;
    $strikejson = $_GET['strike'] === true || $_GET['strike'] === "true" ? "true" : "false";
    $mongoId = new MongoID(toMongoId($id));
    $items->update(array("_id"=>$mongoId), array('$set' => array("strike"=>$strike)));

    $pollQueue = $mongo->white->queue;
    //file_put_contents($pollFile, toMongoId($id) . ":strike-{$strikejson}\n", FILE_APPEND);
    //$pollQueue->update(array("sessid"=>$sessid, "list"=>$_GET['list']), 
    //        array('$set'=>array("list"=>$_GET['list'], "action"=>"strike-{$strikejson}", "timestamp"=>date("U"))), 
    //        array("upsert"=>true));

    print(json_encode(array("status"=>"ok", "msg"=>"Striked item.")));

} else if ($_GET['a'] === "clear-poll-queue") {

    $pollQueue = $mongo->white->queue;
    $pollQueue->remove(array("sessid"=>$sessid, "list"=>$_GET['list']));
    print(json_encode(array("status"=>"ok", "msg"=>"Poll queue cleared.")));

} else if ($_GET['a'] === "poll") {

    /*
    $start = date("U");

    $pollQueue = $mongo->white->queue;
    $pollUsers = $mongo->white->queueUsers;

    // Update your poll time. This is how we determine a client has left.
    $pollUsers->update(array("sessid"=>$sessid), 
            array('$set'=>array("timestamp"=>date("U"))), array("upsert"=>true));

    $pollTimeout = date("U") + (2 * $timeout);
    $data = array("sessid"=>array('$ne'=>$sessid), "list"=>$_GET['list']);//, "timestamp"=>array('$lt'=>$pollTimeout));
    $mr = $pollQueue->find($data)->sort(array("timestamp" => 1));

    while ($mr->count() < 1) {
        if (date("U") - $start > $timeout) {
            print(json_encode(array("status"=>"continue", "msg"=>"No changes.", "list"=>$_GET['list']))); 
            die();
        }
        sleep(1);
        unset($mr);
        $mr = $pollQueue->find($data)->sort(array("timestamp" => 1));
    }

    $queue = array();
    while ($mr->hasNext()) {
        $item = $mr->getNext();
        $queue[] = array("id"=>toHtmlId($item['_id']->{'$id'}), 
                "action"=>$item['action'], "list"=>$_GET['list']);
    }

    $pollQueue->remove(array("sessid"=>$sessid, "list"=>$_GET['list']));
    print(json_encode(array("status"=>"ok", "msg"=>"Detected changes.", "ids"=>$queue))); 
    sleep(5);
    die();
    */

    /*
    $start = date("U");
    while(!file_exists($pollFile)) {
        if (date("U") - $start > $timeout) {
            print(json_encode(array("status"=>"continue", "msg"=>"No changes."))); 
            die();
        }
    }
    $ids = file($pollFile);
    $o = array();
    foreach ($ids as $l) {
        $l = trim($l);
        $al = explode(":", $l);
        $id = $al[0];
        $action = $al[1];
        $o[] = array("id"=>toHtmlId($id), "action"=>$action);
    }
    unlink($pollFile);
    print(json_encode(array("status"=>"ok", "msg"=>"Detected changes.", "ids"=>$o))); 
    */

}
