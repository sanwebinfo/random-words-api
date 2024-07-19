<?php

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=63072000');
header('X-Robots-Tag: noindex, nofollow', true);

// Function to fetch data from a URL using cURL
/**
 * Fetch data from a URL using cURL
 *
 * @param string $url
 * @return string|false
 */
function fetch_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding: identity'));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Accept-Encoding: identity',
        'CF-IPCountry: US',
        'Referer: https://www.google.com/',
    ));

    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        $output = false;
    }

    curl_close($ch);
    return $output;
}

/**
 * Normalize the input word to ASCII.
 *
 * @param string $word The input word to be normalized.
 * @return string|null The normalized word or null if conversion fails.
 */
function normalizeToAscii($words) {
    // Normalize the input word to ASCII
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $words);

    // Check if iconv returned false (conversion failed)
    if ($normalized === false) {
        error_log('iconv failed to convert the word: ' . $words);
        return null;
    }

    // Remove non-ASCII characters
    $normalized = preg_replace('/[^\x20-\x7E]/', '', $normalized);

    return $normalized;
}

// Function to extract the word and definition using regex
/**
 * Extract word and definition from HTML using regex
 *
 * @param string $html
 * @return array
 */
function scrape_data($html) {
    $wordOfDay = [
        'word' => 'Not Found',
        'definition' => 'Not Found',
        'pronunciation' => 'Not Found'
    ];

    if (preg_match('/<div id="random_word">(.+?)<\/div>/s', $html, $word_match)) {
        $word = trim($word_match[1]);
        $words = ucfirst($word);
        $wordOfDay['word'] = normalizeToAscii($words);
    }

    if (preg_match('/<div id="random_word_definition">(.+?)<\/div>/s', $html, $definition_match)) {
        $definition = trim($definition_match[1]);
        $wordOfDay['definition'] = ucfirst($definition);
    }
    
    if(!empty($word)) {
      $word =  normalizeToAscii($words);
      $wordOfDay['pronunciation'] = fetch_pronunciation($word);
    } else {
        $wordOfDay['definition'] = 'Not Found';
    }

    return $wordOfDay;
}

// Function to fetch the pronunciation using the API
/**
 * Fetch pronunciation of a word using an external API
 *
 * @param string $word
 * @return string
 */
function fetch_pronunciation($word) {
    // Pronunciation API : https://github.com/mskian/random-words-api/blob/0e3e062c6b7731c27edddf5a74ad2c5d7519b31c/index.js#L95
    $api_url = 'https://your-pronunciation-api.com/api/' . urlencode($word);
    $response = fetch_data($api_url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            error_log('JSON decode error: ' . json_last_error_msg() . ' with response: ' . $response); // Log JSON errors
        }
    } else {
        error_log('Failed to fetch pronunciation from API.');
    }

    return null;
}

try {
    // Random Words API : https://github.com/mcnaveen/Random-Words-API/blob/ee71f105d10686b0dd7b15ec087fe1a56d995876/routes/en.js#L10
    $url = 'https://scraping-source.com/';
    $html = fetch_data($url);

    if ($html !== false) {
        $data = scrape_data($html);
        echo json_encode([$data], JSON_PRETTY_PRINT);
    } else {
        throw new Exception('Failed to fetch data from the source.');
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch data'], JSON_PRETTY_PRINT);
}

?>