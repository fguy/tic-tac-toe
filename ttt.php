<?php
require_once './game.php';

$user_name = $_POST['user_name'];
$channel_id = $_POST['channel_id'];
$args = explode(' ', strtolower(trim($_POST['text'])));
$command = $args[0];
$game = new Game($channel_id, $user_name);

switch($command) {
	case 'help':
		print_command_list();
		break;
        case 'new':
                $game->newGame($args[1]);
	case 'show':
	default:
		move();
		show();
}

function print_command_list() {
	echo '```', PHP_EOL,
	'new @username?: start a new game', PHP_EOL,
	'show: show the gameboard', PHP_EOL,
	'(x)(y): move to the coordinate \'x\' and \'y\'. x, y should in range of 1 to 3.', PHP_EOL,
	'help: show this list', PHP_EOL,
	'```', PHP_EOL;
}

function show() {
	global $game;
	echo '```', PHP_EOL;
	$game->printBoard();
	echo '```', PHP_EOL;
	echo 'Player O: ', $game->data['O'], PHP_EOL;
	echo 'Player X: ', ($game->data['X'] ? $game->data['X'] : '(Wait to join)'), PHP_EOL;
	if ($game->isOver()) {
		$winner = $game->getWinner();
		echo 'Winner: ', !is_null($winner) ? '@' . $winner : 'Tie', PHP_EOL;
	} else {
		echo 'Next turn: @', $game->getNextPlayer(), PHP_EOL;
	}
	echo PHP_EOL;
	echo 'Type \'/ttt new\' to start a new game', PHP_EOL;
	echo 'Type \'/ttt help\' for more information', PHP_EOL;
}

function move() {
	global $command;
        if(preg_match('/^([1-3])([1-3])$/', $command, $matches)) {
                try {
                        $game->move($matches[1], $matches[2]);
                } catch(Exception $e) {
                        echo $e->getMessage(), PHP_EOL;
                }
        }
}
