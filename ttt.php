<?php
require_once './game.php';

$user_name = $_POST['user_name'];
$channel_id = $_POST['channel_id'];
$command = strtolower(trim($_POST['text']));
$game = new Game($channel_id, $user_name);

switch($command) {
	case 'help':
		print_command_list();
		break;
        case 'new':
                $game->newGame();
	case 'show':
	default:
		show();
}

function print_command_list() {
	echo '```', PHP_EOL,
	'new: start a new game', PHP_EOL,
	'show: show the gameboard', PHP_EOL,
	'(x),(y): mark the coordinate x,y', PHP_EOL,
	'help: show this list', PHP_EOL,
	'```', PHP_EOL;
}

function show() {
	global $game;
	echo '```', PHP_EOL;
	$game->printBoard();
	echo PHP_EOL;
	echo 'Player O: ', $game->data['O'], PHP_EOL;
	echo 'Player X: ', ($game->data['X'] ? $game->data['X'] : '(Wait to join)'), PHP_EOL;
	if ($game->isOver()) {
		$winner = $game->getWinner();
		echo 'Winner: ', !is_null($winner) ? $winner : 'Tie', PHP_EOL;
	}
	echo PHP_EOL;
	echo 'Type \'/ttt new\' to start a new game', PHP_EOL;
	echo 'Type \'/help\' for more information', PHP_EOL;
	echo '```', PHP_EOL;
}

echo "hello world, ${user_name}@${channel_id}: ${text}";
