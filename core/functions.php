<?php

// Task Message
function task_message($message, $title='Task', $color = 34, $firstBreak = true) {
  if($firstBreak == true) {
    echo "\n";
  }
  echo "\033[".$color."m".$title.": ".$message."\n\033[0m";
}

// Debug Message
function debug_message($message, $title='Debug', $color = 33, $firstBreak = false) {
  if (empty($_ENV['DEV_TASK_DEBUG'])) {
    return;
  }
  if ($firstBreak == true) {
    echo "\n";
  }
  echo "\033[".$color."m".$title.": ".$message."\n\033[0m";
}

// Line Break + Color Reset
function lb_cr() {
  echo "\n\033[0m";
}