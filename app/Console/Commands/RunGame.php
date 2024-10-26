<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:game';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A CLI game similar to the Google Dinosaur Game';

    /**
     * Game symbols.
     */
    protected $dino = '🐘';

    protected $obstacle = '🐍';

    protected $emptySpace = ' ';

    protected $ground = '_';

    /**
     * Game state variables.
     */
    protected $position = 5; // Fixed dinosaur horizontal position

    protected $score = 0;

    protected $isJumping = false;

    protected $jumpDuration = 3; // Number of frames the jump lasts

    protected $jumpCounter = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Welcome to the CLI ElePHPant Game!');
        $this->info('Avoid the Pythons!');
        $this->info('Press Enter to start...');
        readline();

        // Disable input buffering
        $this->disableInputBuffering();

        $this->runGame();

        // Restore input buffering after the game ends
        $this->restoreInputBuffering();
    }

    /**
     * Runs the game loop.
     */
    protected function runGame()
    {
        // Game settings
        $gameSpeed = 200000; // Microseconds (0.2 seconds)
        $obstacleFrequency = 5; // Every 5 steps, an obstacle may appear

        // Initialize game variables
        $steps = 0;
        $obstacles = [];

        // Clear the screen
        $this->clearScreen();

        // Game loop
        while (true) {
            $steps++;
            $this->score++;

            // Randomly add obstacles
            if ($steps % $obstacleFrequency === 0) {
                if (rand(0, 1)) {
                    $obstacles[] = 50; // Obstacle appears at the end of the screen
                }
            }

            // Update obstacles positions
            foreach ($obstacles as $key => $obstaclePosition) {
                $obstacles[$key]--;

                // Remove obstacle if it goes off-screen
                if ($obstacles[$key] < 0) {
                    unset($obstacles[$key]);
                }
            }

            // Handle user input for jumping
            $this->handleInput();

            // Update jumping state
            if ($this->isJumping) {
                $this->jumpCounter++;

                if ($this->jumpCounter >= $this->jumpDuration) {
                    $this->isJumping = false;
                    $this->jumpCounter = 0;
                }
            }

            // Check for collision
            if (! $this->isJumping && in_array($this->position, $obstacles)) {
                $this->gameOver();

                return;
            }

            // Render the game frame
            $this->renderFrame($obstacles);

            if ($gameSpeed > 50000) {
                $gameSpeed -= 500; // Increase game speed over time
            }
            // Sleep for the game speed duration
            usleep($gameSpeed);
        }
    }

    /**
     * Renders the game frame.
     */
    protected function renderFrame($obstacles)
    {
        $this->clearScreen();

        // Build the top line
        $topLine = '';

        for ($i = 0; $i < 50; $i++) {
            if ($i === $this->position && $this->isJumping) {
                // Dinosaur is jumping on the top line
                $topLine .= $this->dino;
            } else {
                $topLine .= $this->emptySpace;
            }
        }

        // Build the bottom line
        $bottomLine = '';

        for ($i = 0; $i < 50; $i++) {
            if ($i === $this->position && ! $this->isJumping) {
                // Dinosaur is on the ground
                $bottomLine .= $this->dino;
            } elseif (in_array($i, $obstacles)) {
                // Obstacle position
                $bottomLine .= $this->obstacle;
            } else {
                // Ground
                $bottomLine .= $this->ground;
            }
        }

        // Display score and game lines
        $this->info("Score: $this->score");
        $this->line($topLine);
        $this->line($bottomLine);
        $this->line('');
        $this->info('Press SPACE to jump.');
    }

    /**
     * Handles user input.
     */
    protected function handleInput()
    {
        // Non-blocking input handling
        stream_set_blocking(STDIN, false);
        $input = fread(STDIN, 1);

        // If the user presses the space bar
        if ($input === ' ') {
            $this->jump();
        }

        // Restore blocking mode
        stream_set_blocking(STDIN, true);
    }

    /**
     * Handles the jump action.
     */
    protected function jump()
    {
        if (! $this->isJumping) {
            $this->isJumping = true;
            $this->jumpCounter = 0;
        }
    }

    /**
     * Disables input buffering.
     */
    protected function disableInputBuffering()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            system('stty -icanon -echo');
        }
    }

    /**
     * Restores input buffering.
     */
    protected function restoreInputBuffering()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            system('stty sane');
        }
    }

    /**
     * Clears the console screen.
     */
    protected function clearScreen()
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            system('cls');
        } else {
            system('clear');
        }
    }

    /**
     * Handles game over logic.
     */
    protected function gameOver()
    {
        $this->clearScreen();
        $this->error('Game Over!');
        $this->info("Your final score is: $this->score");

        // Restore input buffering after the game ends
        $this->restoreInputBuffering();
    }
}
