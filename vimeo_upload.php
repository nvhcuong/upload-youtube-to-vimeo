<?php
require("vimeo/autoload.php"); // upload Vimeo API to vimeo folder on root

$client_id = 'xxxx'; // Vimeo Client Id
$client_secret = 'xxxx'; // Vimeo Client Secret
$access_token = 'xxxx'; // Vimeo Apps Access Token 

// YouTube ID 
$videoId = (string) $_REQUEST['ytid'];
$videoId = trim($videoId);

$lib = new \Vimeo\Vimeo($client_id, $client_secret, $access_token);

try {

    parse_str( file_get_contents( 'http://youtube.com/get_video_info?video_id=' . $videoId ), $videoData );

    $streamUrl = getMP4FromEncodedStream( explode( ',', $videoData['url_encoded_fmt_stream_map'] ) );
    // If we aren't able to open the stream.
    if ( ( $read = fopen( $streamUrl, 'r' ) ) === false )
    {
        throw new Exception('Could not load Stream URL.' . $streamUrl);
    }
    fclose($read);

    if ( $streamUrl  === NULL )
    {
        throw new Exception( 'No MP4 video source was able to be located.' );
    }

    $video_response = $lib->request(
        '/me/videos',
        [
            'upload' => [
                'approach' => 'pull',
                'link' => $streamUrl
            ],
        ],
        'POST'
    );

    $result = $lib->request($video_response['body']['uri'], array(
        'name' => $videoData['title'],
    ), 'PATCH');

    if($result['status']==200) {
        print 'Upload Vimeo successful.';
    }
}
catch (Exception $e) {
    
    print '<strong>Error:</strong> ' . $e->getMessage( );
}

function getMP4FromEncodedStream( $streams ) {
    foreach( $streams as $stream )
    {
        // Decode this stream's data.
        parse_str( $stream, $data );

        // If we found our MP4 stream source.
        if ( stripos( $data['type'], 'video/mp4' ) === 0 )
        {
            return $data['url'];
        }
    }
    // We didn't find any, whoops..
    return NULL;
}
