<?php
$user_name = $_POST['user_name'];
$channel_id = $_POST['channel_id'];
$text = $_POST['text'];

echo "hello world, ${user_name}@${channel_id}: ${text}";
