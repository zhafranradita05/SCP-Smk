<?php
require 'C:/laragon/www/SCP_project/scp_web/vendor/autoload.php';

$parser = new Dotenv\Parser\Parser();
$res1 = $parser->parse('KEY="jmff fumw uqjx mlyn"');
echo "Quotes: " . $res1[0]->getValue()->get()->getChars() . "\n";

$res2 = $parser->parse('KEY=jmfffumwuqjxmlyn');
echo "No spaces: " . $res2[0]->getValue()->get()->getChars() . "\n";

