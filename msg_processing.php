<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 *
 * Put your custom bot intelligence here!
 */

// This file assumes to be included by pull.php or
// push.php right after receiving a new message.
// It also assumes that the message data is stored
// inside a $message variable.

// Message object structure: {
//     "message_id": 123,
//     "from": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast"
//     },
//     "chat": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast",
//       "type": "private"
//     },
//     "date": 1460036220,
//     "text": "Text"
//   }
$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

// Load status from DB
$status = db_row_query("SELECT state, lat, lng FROM status WHERE user_id = $from_id");

// 0: default
// 1: ready (has location)
// 2: recording
$conversation_status = ($status) ? $status[0] : 0;
$lat = ($status) ? $status[1] : null;
$lng = ($status) ? $status[2] : null;

echo "Status $conversation_status, $lat, $lng" . PHP_EOL;

if (isset($message['text'])) {
    // We got an incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
        echo 'Received /start command!' . PHP_EOL;

        telegram_program_combo($chat_id, 'BOTCMD START');
    }
    else if (strpos($text, "/reset") === 0) {
        db_perform_action("DELETE FROM `status` WHERE `user_id` = $from_id");
        $words_removed = db_perform_action("DELETE FROM `word_votes` WHERE `user_id` = $from_id");

        telegram_send_message($chat_id, "Rimosse $words_removed parole.");

        telegram_program_combo($chat_id, 'BOTCMD START');
    }
    else if (strpos($text, "/results") === 0) {
        if($conversation_status == 0) {
            telegram_send_message($chat_id, 'Non conosco la tua posizione, dimmi dove sei prima.');
        }
        else {
            generate_results($chat_id, $lat, $lng);
        }
    }
    else {
        echo "Received message: $text" . PHP_EOL;

        $aiml_response = telegram_program_combo($chat_id, $text);

        if($conversation_status == 2 && strpos($aiml_response, 'BOTCMD') !== 0) {
            //Bot active, record words
            $clean_text = trim(preg_replace('/[^\wìàòùèéáíóúüäö]+/', ' ', mb_strtolower($text)));
            $words = explode(' ', $clean_text);

            foreach($words as $w) {
                echo "Inserting word $w" . PHP_EOL;
                if(!db_perform_action("INSERT INTO `word_votes` VALUES($from_id, '$w', $lat, $lng)")) {
                    error_log('Failed to store word in DB');
                    exit;
                }
            }
        }

        if(strpos($aiml_response, 'BOTCMD ACTIVE') === 0) {
            db_perform_action("REPLACE INTO `status` VALUES($from_id, 2, $lat, $lng)");

            $text_response = substr($aiml_response, strlen('BOTCMD ACTIVE') + 1);
            telegram_send_message($chat_id, $text_response);

        }
        else if(strpos($aiml_response, 'BOTCMD READY') === 0) {
            db_perform_action("REPLACE INTO `status` VALUES($from_id, 1, $lat, $lng)");

            $text_response = substr($aiml_response, strlen('BOTCMD READY') + 1);
            telegram_send_message($chat_id, $text_response);
        }
        else if(strpos($aiml_response, 'BOTCMD LOCATION') === 0) {
            $target_location = urlencode(substr($aiml_response, strlen('BOTCMD LOCATION') + 1));
            echo "Seeking location $target_location" . PHP_EOL;

            $request_url = "http://dev.virtualearth.net/REST/v1/Locations?query=$target_location&includeNeighborhood=0&maxResults=1&key=" . BING_API;

            //telegram_send_message($chat_id, $request_url);

            $handle = prepare_curl_api_request($request_url, 'GET');
            $response = perform_curl_request($handle);
            if($response === false) {
                error_log('Failed to perform request.');
                exit;
            }
            $json = json_decode($response, true);
            if(!$json['resourceSets'] || sizeof($json['resourceSets']) < 1) {
                error_log('Response contains no resource sets');
                exit;
            }

            $target_lat = $json['resourceSets'][0]['resources'][0]['point']['coordinates'][0];
            $target_lng = $json['resourceSets'][0]['resources'][0]['point']['coordinates'][1];

            if($target_lat && $target_lng) {
                echo "Resolved $target_lat,$target_lng" . PHP_EOL;

                telegram_send_location($chat_id, $target_lat, $target_lng);

                db_perform_action("REPLACE INTO `status` VALUES($from_id, 1, $target_lat, $target_lng)");

                telegram_program_combo($chat_id, 'BOTCMD READY');
            }
            else {
                telegram_send_message($chat_id, 'Non penso di aver capito. Forse è meglio se mi mandi direttamente la posizione.');
            }
        }
        else if(strpos($aiml_response, 'BOTCMD RESULTS') === 0) {
            generate_results($chat_id, $lat, $lng);
        }
    }
}
else if (isset($message['location'])) {
    $lat = $message['location']['latitude'];
    $lng = $message['location']['longitude'];

    db_perform_action("REPLACE INTO `status` VALUES($from_id, 1, $lat, $lng)");

    telegram_program_combo($chat_id, 'BOTCMD READY');

    //$handle = prepare_curl_api_request("http://dev.virtualearth.net/REST/v1/Locations/$lat,$lng?key=" . BING_API, 'GET');

    /*$response = perform_curl_request($handle);
    if($response === false) {
        error_log('Failed to perform request.');
        exit;
    }

    $json = json_decode($response, true);
    if(!$json['resourceSets']) {
        error_log('Response contains no resource sets');
        exit;
    }

    $locality = $json['resourceSets'][0]['resources'][0]['address']['locality'];

    echo "L'utente è in $locality" . PHP_EOL;

    $info = db_row_query("SELECT * FROM `localita` WHERE `LOCALITA` = '$locality'");
    $altitudine = $info[3];
    $popolazione = $info[4];

    telegram_send_message($chat_id, "Ti trovi a $locality, altitudine: $altitudine, popolazione: $popolazione");*/
}
else {
    //telegram_send_message($chat_id, 'Sorry, I understand only text messages at the moment!');
}
?>

