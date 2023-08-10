<?php

if (getenv('OPENAI_API_KEY')===false || getenv('OPENAI_CHATGPT_MODEL')===false) {
    exit('Missing required environment configuration.');
}

// Configuration for OpenAI API
define('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

// Read the verbs, conjunctions, and Spongebob situations from separate text files
$danish_verbs = file('danish_verbs.txt', FILE_IGNORE_NEW_LINES);
$danish_conjunctions = file('danish_conjunctions.txt', FILE_IGNORE_NEW_LINES);
$spongebob_situations = file('spongebob_situations.txt', FILE_IGNORE_NEW_LINES);

// Fetch random Danish verb, conjunction, and Spongebob situation
$random_verb = explode("\t", $danish_verbs[array_rand($danish_verbs)]);
$random_conjunction = explode("\t", $danish_conjunctions[array_rand($danish_conjunctions)]);
$random_situation = $spongebob_situations[array_rand($spongebob_situations)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentence = $_POST['sentence'];
    // Validate sentence grammar using OpenAI API
    $message = validate_sentence($sentence);
    $message = trim($message, '"');
    $message = trim($message, "'");
}

function validate_sentence($sentence) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, OPENAI_API_ENDPOINT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => getenv('OPENAI_CHATGPT_MODEL'),
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Check Danish grammar of this sentence. Accept absurdity. Reply only with the corrected sentence.',
            ],
            [
                'role' => 'user',
                'content' => sprintf('"%s"', $sentence),
            ],
        ],
        'max_tokens' => 400,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    if (!$response || curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
        echo '<pre>';var_dump($response);echo '</pre>';
        return false; // Fail silently if API fails
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danish Sentence Generator</title>
</head>
<body>
    <h1>Danish Sentence Generator</h1>
    <p>Random Verb: <abbr title="<?= implode("\n", $random_verb)?>"><?= $random_verb[0]?></abbr></p>
    <p>Random Conjunction: <abbr title="<?= implode("\n", $random_conjunction)?>"><?= $random_conjunction[0]?></abbr></p>
    <p>Random Spongebob Situation: <strong><?= $random_situation?></strong></p>
    <form action="" method="post">
        <label for="sentence">Write a sentence using the above values:</label><br>
        <textarea name="sentence" id="sentence" cols="50" rows="5"></textarea><br><br>
        <input type="submit" value="Validate">
    </form>
    <table>
    <?php if (isset($sentence)): ?>
        <tr>
            <th>You entered:</th>
            <td><pre><?php echo $sentence; ?></pre></td>
        </tr>
    <?php endif; ?>
    <?php if (isset($message)): ?>
        <tr>
            <th>ChatGPT says:</th>
            <td><pre><?php echo $message; ?></pre></td>
        </tr>
        <tr>
            <th></th>
            <td><small>(If in doubt, consult your teacher.)</small></td>
        </tr>
    <?php endif; ?>
    </table>
    <style type="text/css">
        abbr {
            cursor: help;
            font-weight: bold;
        }
        table {
            text-align: left;
        }
        pre {
            white-space: pre-wrap;       /* Since CSS 2.1 */
            white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
            white-space: -pre-wrap;      /* Opera 4-6 */
            white-space: -o-pre-wrap;    /* Opera 7 */
            word-wrap: break-word;       /* Internet Explorer 5.5+ */
        }
    </style>
</body>
</html>
