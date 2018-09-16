#!/usr/bin/php
<?php
/**
 * PHP Snake CLI Game
 *
 * This is an implementation of the classic snake game.
 *
 * PHP version 7.2
 *
 * LICENSE:
 * MIT License
 *
 * Copyright (c) 2018 Joakim Winum Lien
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @version $Release: 2.1.0 $
 * @since File available since Release: 1.0.0
 */

/**
 * init
 */

$engine = new PhpGameEngine();


/**
 * settings
 */

$framesPerSecondHorizontal = 16;
$diffConstant = .65;

$engine->setFpsHorizontal($framesPerSecondHorizontal);
$engine->setFpsFactor($diffConstant);
$engine->setFpsVertical((int)($engine->getFpsHorizontal()*$engine->getFpsFactor()));
$engine->setFps($engine->getFpsHorizontal());

$pointDot = null;

$spriteChar = json_decode('"\uD83D\uDC0D"');
$snakeSprite = "\033[30;1m".$spriteChar."\033[0m";
$spriteChar = json_decode('"\u2BC8"');
$rightPointingTriangleSprite = "\033[30;1m".$spriteChar."\033[0m";


/**
 * global variables
 */

$board_x=80;
$board_y=24;
$score = 0;
$snakeLen = 0;
$snakeOldLen = 0;
$totalNumberOfFrames = 0;
$increaseInterval = 1;
$globalGameTitle = $snakeSprite." \033[38;5;46mPHP Snake\033[0m ".$rightPointingTriangleSprite;
$key = null;
$blankBoard = null;
$doIncreasePlayer = false;
$updatePointDot = false;
$devMode = false;
$cacheDraw = false;
$leftMargin = " ";


/**
 * game setup (to be run once)
 */

// create the background and frame wall
$background = createBackground();
$frameWall = createFrameWall();

// draw the background and frame onto the board and store it in the draw cache
$cacheDraw = true;
draw(array(
    $background,
    $frameWall
));

// create the player
$player = createPlayer();


/**
 * functions
 */

/**
 * create functions
 */

/**
 * @return array
 */
function createPlayer()
{
    $spriteChar = json_decode('"\u2D46"');
    $playerSprite = "\033[30;1m".$spriteChar."\033[0m";
    return array(array(40, 12, $playerSprite), array(39, 12, $playerSprite), array(38, 12, $playerSprite));
}

/**
 * @return array
 */
function createFrameWall()
{
    global $board_x;
    global $board_y;

    $frameWallArray = [];

    $spriteChar = "#";
    $wallSprite = "\033[38;5;237;48;5;237m".$spriteChar."\033[0m";

    for ($i = 0; $i < $board_x; $i++) {
        for ($j = 0; $j < $board_y; $j++) {
            if ($i == 0 || $i == $board_x - 1 || $j == 0 || $j == $board_y - 1) {
                // create the frame wall
                $frameWallArray[] = array($i, $j, $wallSprite);
            }
        }
    }

    return $frameWallArray;
}

/**
 * @return array
 */
function createBackground()
{
    global $board_x;
    global $board_y;

    $backgroundArray = [];

    $backgroundSprite = " ";

    for ($i = 0; $i < $board_x; $i++) {
        for ($j = 0; $j < $board_y; $j++) {
            // create the background
            $backgroundArray[] = array($i, $j, $backgroundSprite);
        }
    }

    return $backgroundArray;
}

/**
 * @param $entities
 * @return string
 */
function draw($entities)
{
    global $board_x;
    global $board_y;
    global $blankBoard;
    global $cacheDraw;
    global $leftMargin;

    $board = "";

    // create a blank board array if it is not already done
    if (!isset($blankBoard["0,0"])) {
        // create the board array
        $blankBoard = [];

        for($j=0; $j < $board_y; $j++) {
            for($i=0; $i < $board_x; $i++) {
                $blankBoard["".$i.",".$j.""] = "%";
            }
        }
    }
    $boardArray = $blankBoard;

    // draw all the entities onto the board array
    foreach ($entities as $entity) {
        if (isset($entity[0][0])) {
            foreach ($entity as $coo) {
                $boardArray["".$coo[0].",".$coo[1].""] = $coo[2];
            }
        } else {
            $boardArray["".$entity[0].",".$entity[1].""] = $entity[2];
        }
    }

    // store the current entities in the draw cache
    if ($cacheDraw) {
        $blankBoard = $boardArray;
        $cacheDraw = false;
    }

    // convert the board array to string
    for($j=0; $j < $board_y; $j++) {
        for($i=0; $i < $board_x; $i++) {
            // add margin on the left side of the board
            if ($i == 0) {
                $board .= $leftMargin;
            }

            // draw the board array
            $board .= $boardArray["".$i.",".$j.""];

            // add a line break on end of each line
            if ($i == $board_x-1) {
                $board .= "\n";
            }
        }
    }

    // return the board string
    return $board;
}


/**
 * other functions
 */

/**
 * @param $player
 * @return array
 */
function player($player)
{
    global $key;
    global $snakeLen;

    $snakeLen = count($player);
    $headDirection = null;
    $north = "north";
    $south = "south";
    $west = "west";
    $east = "east";

    // determine the direction of the players head
    if ($player[0][0] > $player[1][0]) {
        $headDirection = $east;
    } else if ($player[0][0] < $player[1][0]) {
        $headDirection = $west;
    } else if ($player[0][1] < $player[1][1]) {
        $headDirection = $north;
    } else if ($player[0][1] > $player[1][1]) {
        $headDirection = $south;
    }

    // move player with or without input
    if (!is_null($key)) {
        if ($key == "w" && ($headDirection == $west || $headDirection == $east)) {
            $player = movePlayer($player, $north);
        } else if ($key == "a" && ($headDirection == $north || $headDirection == $south)) {
            $player = movePlayer($player, $west);
        } else if ($key == "s" && ($headDirection == $west || $headDirection == $east)) {
            $player = movePlayer($player, $south);
        } else if ($key == "d" && ($headDirection == $north || $headDirection == $south)) {
            $player = movePlayer($player, $east);
        }
    } else {
        $player = movePlayer($player, $headDirection);
    }

    return $player;
}

/**
 * @param $player
 * @param $direction
 * @return array
 */
function movePlayer($player, $direction)
{
    global $engine;

    $north = "north";
    $south = "south";
    $west = "west";
    $east = "east";

    // take off the tail
    if (!increasePlayer()) {
        array_pop($player);
    }

    // create the new head
    $newHead = $player[0];

    // move the new head
    if ($direction == $north) {
        $newHead[1] -= 1;
        $engine->setFps($engine->getFpsVertical());
    } else if ($direction == $west) {
        $newHead[0] -= 1;
        $engine->setFps($engine->getFpsHorizontal());
    } else if ($direction == $south) {
        $newHead[1] += 1;
        $engine->setFps($engine->getFpsVertical());
    } else if ($direction == $east) {
        $newHead[0] += 1;
        $engine->setFps($engine->getFpsHorizontal());
    }

    // add the new head on
    $player = array_merge(array($newHead), $player);

    return $player;
}

/**
 * @param bool $set
 * @param null $int
 * @return bool
 */
function increasePlayer($set = false, $int = null)
{
    global $snakeLen;
    global $snakeOldLen;
    global $doIncreasePlayer;
    global $increaseInterval;
    global $score;

    $score = $snakeLen-3;

    if (isset($int)) {
        $increaseInterval = $int;
    }

    if ($set) {
        $snakeOldLen = $snakeLen;
    }

    if ($snakeLen >= $snakeOldLen+$increaseInterval) {
        $doIncreasePlayer = false;
    } else {
        $doIncreasePlayer = true;
    }

    return $doIncreasePlayer;
}

/**
 * @param $player
 * @param $pointDot
 */
function collisionTesting($player, $pointDot)
{
    global $updatePointDot;
    global $frameWall;

    // players head
    $playerHead = $player[0];

    // check for collision with wall
    foreach ($frameWall as $wall) {
        if($wall[0] == $playerHead[0] && $wall[1] == $playerHead[1]) {
            gameOver();
        }
    }

    // player eats point dot
    if ($playerHead[0] == $pointDot[0] && $playerHead[1] == $pointDot[1]) {
        increaseplayer(true);
        $updatePointDot = true;
    }

    // check if player head touches its own tail
    foreach($player as $key => $part) {
        if ($key == 0) {
            // skip head
            continue;
        }
        if ($playerHead[0] == $part[0] && $playerHead[1] == $part[1]) {
            gameOver();
        }
    }
}

/**
 */
function gameOver()
{
    global $board;
    global $score;
    global $devMode;
    global $globalGameTitle;
    global $engine;
    global $leftMargin;

    $engine->clearScreen();

    echo $leftMargin;
    echo $globalGameTitle;
    echo "\033[38;5;249m";
    echo " Game Over ";
    $padScore = str_pad($score, 4, "0", STR_PAD_LEFT);
    echo "\033[0m";
    $spriteChar = json_decode('"\u2BC8"');
    $rightPointingTriangleSprite = "\033[30;1m".$spriteChar."\033[0m";
    echo $rightPointingTriangleSprite;
    echo "\033[38;5;249m";
    echo " Score: ".$padScore;
    if ($devMode) {
        echo " [DevMode]";
    }
    echo "\n";
    echo "\033[0m";
    echo $board;

    $engine->resetTty();

    exit();
}


/**
 * @param $pointDot
 * @param $player
 * @return array|bool
 */
function generateNewCoordinates($pointDot, $player)
{
    global $board_x;
    global $board_y;

    while (true) {
        // get random coordinates
        $rand_x = rand(1, $board_x-2);
        $rand_y = rand(1, $board_y-2);

        // check if the player already is on the new coordinates
        foreach ($player as $part) {
            if ($part[0] == $rand_x && $part[1] == $rand_y) {
                continue 2;
            }
        }

        // check if the new coordinates are in the old place of the point dot
        if ($pointDot[0] == $rand_x && $pointDot[1] == $rand_y) {
            continue;
        }

        break;
    }

    if (!isset($rand_x) || !isset($rand_y)) {
        return false;
    }

    return array($rand_x, $rand_y);
}

/**
 * @param $player
 * @param null $pointDot
 * @return array|null
 */
function pointDot($player, $pointDot = null)
{
    global $updatePointDot;

    $spriteChar = json_decode('"\u25CF"');
    $pointDotSprite = "\033[30;1m".$spriteChar."\033[0m";

    // generate the first dot
    if (!isset($pointDot)) {
        $coordinates = generateNewCoordinates(null, $player);
        $pointDot = array($coordinates[0], $coordinates[1], $pointDotSprite);
    }

    // update the dot
    if ($updatePointDot) {
        $coordinates = generateNewCoordinates($pointDot, $player);
        $pointDot = array($coordinates[0], $coordinates[1], $pointDotSprite);
        $updatePointDot = false;
    }

    return $pointDot;
}

/**
 * @return string
 */
function printStats()
{
    global $globalGameTitle;
    global $score;
    global $snakeLen;
    global $totalNumberOfFrames;
    global $engine;
    global $devMode;
    global $leftMargin;

    // add left margin
    $string = $leftMargin;

    // display game name
    $string .= $globalGameTitle;

    // start color
    $string .= "\033[38;5;249m";

    // display score
    $padScore = str_pad($score, 4, "0", STR_PAD_LEFT);
    $string .= " points: ".$padScore;

    // display extra stats in dev mode
    if ($devMode) {
        // display snake length
        $padSnakeLen = str_pad($snakeLen, 4, "0", STR_PAD_LEFT);
        $string .= ", length: ".$padSnakeLen;

        // display total number of frames
        $padFrames = str_pad($totalNumberOfFrames, 4, "0", STR_PAD_LEFT);
        $string .= ", total frames: ".$padFrames;

        // display frames per second
        $padFPS = str_pad($engine->getFps(), 4, "0", STR_PAD_LEFT);
        $string .= ", FPS: ".$padFPS;
    }

    // end color
    $string .= "\033[0m";

    // add new line
    $string .= "\n";

    return $string;
}

/**
 */
function keyActions()
{
    global $devMode;
    global $updatePointDot;
    global $engine;
    global $key;

    // do actions upon certain key presses
    if (!is_null($key)) {
        if ($key == "q") {
            // exit the game
            $engine->resetTty();
            exit();
        } else if ($key == "i") {
            // increase length
            if ($devMode) {
                increasePlayer(true,40);
            }
        } else if ($key == "u") {
            // increase length
            if ($devMode) {
                increasePlayer(true,140);
            }
        } else if ($key == "r") {
            // reset length increase
            if ($devMode) {
                increasePlayer(null,1);
            }
        } else if ($key == "e") {
            // increase fps
            if ($devMode) {
                $engine->setFpsHorizontal(25);
                $engine->setFpsVertical((int)($engine->getFpsHorizontal()*$engine->getFpsFactor()));
            }
        } else if ($key == "n") {
            // replace point dot
            if ($devMode) {
                $updatePointDot = true;
            }
        } else if ($key == "t") {
            // activate dev mode
            if (!$devMode) {
                $devMode = true;
            }
        }
    }
}


/**
 * Class PhpGameEngine
 *
 * The game engine takes care of mainly three things:
 * * clearing the screen
 * * syncing the game loop
 * * detecting key presses
 *
 * Remember to call the TTY reset function before exit if the built in key
 * detection function have been used.
 *
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @version $Release: 2.1.0 $
 * @since Class available since Release: 1.0.0
 */
class PhpGameEngine
{
    private $gameTimeBeginning;
    private $gameTimeEnd;
    private $fps;
    private $fpsHorizontal;
    private $fpsVertical;
    private $fpsFactor;
    private $os;
    private $keyReadTimeout;
    private $ttySettings;

    /**
     * @return mixed
     */
    public function getGameTimeBeginning()
    {
        return $this->gameTimeBeginning;
    }

    /**
     * @param mixed $gameTimeBeginning
     */
    public function setGameTimeBeginning($gameTimeBeginning)
    {
        $this->gameTimeBeginning = $gameTimeBeginning;
    }

    /**
     * @return mixed
     */
    public function getGameTimeEnd()
    {
        return $this->gameTimeEnd;
    }

    /**
     * @param mixed $gameTimeEnd
     */
    public function setGameTimeEnd($gameTimeEnd)
    {
        $this->gameTimeEnd = $gameTimeEnd;
    }

    /**
     * @return mixed
     */
    public function getFps()
    {
        return $this->fps;
    }

    /**
     * @param mixed $fps
     */
    public function setFps($fps)
    {
        $this->fps = $fps;
    }

    /**
     * @return mixed
     */
    public function getFpsHorizontal()
    {
        return $this->fpsHorizontal;
    }

    /**
     * @param mixed $fpsHorizontal
     */
    public function setFpsHorizontal($fpsHorizontal)
    {
        $this->fpsHorizontal = $fpsHorizontal;
    }

    /**
     * @return mixed
     */
    public function getFpsVertical()
    {
        return $this->fpsVertical;
    }

    /**
     * @param mixed $fpsVertical
     */
    public function setFpsVertical($fpsVertical)
    {
        $this->fpsVertical = $fpsVertical;
    }

    /**
     * @return mixed
     */
    public function getFpsFactor()
    {
        return $this->fpsFactor;
    }

    /**
     * @param mixed $fpsFactor
     */
    public function setFpsFactor($fpsFactor)
    {
        $this->fpsFactor = $fpsFactor;
    }

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param mixed $os
     */
    public function setOs($os)
    {
        $this->os = $os;
    }

    /**
     * @return mixed
     */
    public function getKeyReadTimeout()
    {
        return $this->keyReadTimeout;
    }

    /**
     * @param mixed $keyReadTimeout
     */
    public function setKeyReadTimeout($keyReadTimeout)
    {
        $this->keyReadTimeout = $keyReadTimeout;
    }

    /**
     * @return mixed
     */
    public function getTtySettings()
    {
        return $this->ttySettings;
    }

    /**
     * @param mixed $ttySettings
     */
    public function setTtySettings($ttySettings)
    {
        $this->ttySettings = $ttySettings;
    }

    /**
     * @return array
     */
    public function microtimeNow()
    {
        $microtime = microtime();

        $time = explode(" ", $microtime);
        $timestamp = $time[1];
        $time = explode(".", $time[0]);
        $microseconds = (int)$time[1]/100;

        return array($microseconds, $timestamp);
    }

    /**
     * this function sets a sleep depending on chosen fps
     *
     * Put this at the end of a game loop to sync with the fps you have chosen.
     *
     * @return bool
     */
    public function fpsSync()
    {
        // get the time from the bottom of the code
        $this->setGameTimeEnd($this->microtimeNow());

        $timeBeginning = $this->getGameTimeBeginning()[0];
        $timeEnd = $this->getGameTimeEnd()[0];

        if (!isset($timeBeginning)) {
            $this->setKeyReadTimeout(100);
            $this->setGameTimeBeginning($this->microtimenow());
            return false;
        }

        // the loop is taking longer than 1 second
        if ($timeEnd[1] - $timeBeginning[1] > 1) {
            $this->setKeyReadTimeout(100);
            $this->setGameTimeBeginning($this->microtimenow());
            return false;
        }

        $fps = $this->getFps(); // frames per second

        $microsecond = 10**6; // 1 second = 1*10^6 microseconds

        if ($timeEnd > $timeBeginning) {
            $time = $timeEnd - $timeBeginning;
        } else {
            $time = $microsecond + $timeEnd - $timeBeginning;
        }

        if ($time > $microsecond) {
            // the code is going too slow, no wait
            $this->setKeyReadTimeout(100);
            $this->setGameTimeBeginning($this->microtimenow());
            return false;
        }

        $framesPerMicrosecond = (int)$microsecond/$fps;

        $pause = $framesPerMicrosecond - $time;

        if ($pause < 0) {
            // the code is going too slow, no wait
            $this->setKeyReadTimeout(100);
            $this->setGameTimeBeginning($this->microtimenow());
            return false;
        }

        // actively adjust the key reading timeout
        $this->setKeyReadTimeout((int)$pause/10);

        // sleep
        usleep($pause);

        // get the time from the beginning of the code
        $this->setGameTimeBeginning($this->microtimenow());

        return true;
    }

    /**
     * clears the screen
     *
     * It will detect the current operation system and choose which system
     * screen clear function to use.
     */
    public function clearScreen()
    {
        $os = $this->getOs();

        // check which os the host is running
        if (!isset($os)) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // windows
                $this->setOs("windows");
            } else {
                // other (linux)
                $this->setOs("other");
            }
            $os = $this->getOs();
        }

        // clear the screen
        if ($os == "windows") {
            // windows
            system('cls');
        } else {
            // other (linux)
            system('clear');
        }
    }

    /**
     * returns the key character typed
     *
     * Can cause high CPU usage.
     * Timeout variable will be auto updated by the fpsSync function.
     *
     * @return bool|null|string
     */
    public function readKeyPress()
    {
        $this->modifyTty();
        $timeout = $this->getKeyReadTimeout(); // microseconds
        $microsecond = 10**6; // 1 second = 1*10^6 microseconds

        // set the timeout variable if it has not already been set
        if (!isset($timeout)) {
            $timeout = 200*10**3; // recommended value
            $this->setKeyReadTimeout($timeout);
        }

        $stdin = STDIN;
        $read = array($stdin);
        $null = null; // temporary variable due to a Zend Engine limitation
        $tv_sec = floor($timeout / $microsecond); // timeout variable in seconds
        $tv_usec = $timeout % $microsecond; // timeout variable in microseconds

        // check if any key is pressed within the timeout period
        if (stream_select($read,$null,$null, $tv_sec, $tv_usec) != 1) {
            return null;
        }

        // return the key pressed
        return fread($stdin, 1);
    }

    /**
     * @return bool
     */
    public function modifyTty()
    {
        $ttySettings = $this->getTtySettings();

        if(isset($ttySettings)) {
            return false;
        }

        // save current tty config
        $ttySettings = exec("stty -g");
        $this->setTtySettings($ttySettings);

        // change tty to be able to read in characters
        system("stty -icanon");

        return true;
    }

    /**
     * @return bool
     */
    public function resetTty()
    {
        $ttySettings = $this->getTtySettings();

        if(!isset($ttySettings)) {
            return false;
        }

        // reset tty back to its original state
        system("stty '".$ttySettings."'");

        return true;
    }
}


/**
 * game loop
 */
while (true)
{
    // clear the screen
    $engine->clearScreen();

    // print stats
    $stats = printStats();
    echo $stats;

    // update the player
    $player = player($player);

    // update the point dot
    $pointDot = pointDot($player, $pointDot);

    // collision testing
    collisionTesting($player, $pointDot);

    // draw the board with all the entities on it and print it out
    $board = draw(array(
        $pointDot,
        $player
    ));
    echo $board;

    // take key input
    echo $leftMargin;
    $key = $engine->readKeyPress();

    // perform key actions
    keyActions();

    // count frames
    $totalNumberOfFrames += 1;

    // sync game loop to the saved fps value
    $engine->fpsSync();
}
