<?php
/*
Project Name: Simple Commit Notifier
Description: A simple Post-Receive URL implementation to send emails containing commit information
Version: 1.0
Author: Mert Yazicioglu
Author URI: http://www.mertyazicioglu.com
License: GPL2
*/

/*  Copyright 2011  Mert Yazicioglu  (email : mert@mertyazicioglu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// There is nothing to do if there is no payload sent
if ( ! $_POST['payload'] )
    exit();

/**
 * Please fill in the following 3 settings
 */

// Path to the file that holds the email adresses we will send the email to
$contacts_file = "contacts";

// The sender email address
$sender = "";

// The reply-to email address
$reply_to = "";

/**
 * Please do not touch anything below unless you know what you're doing
 */

// To store the list of email addresses we will send the email to
$to = "";

// Open the contacts file to read email addresses
$f = @fopen( $contacts_file, "r" );

// Read email addresses from each line to form the "to list"
if ( $f ) {
    while ( ! feof( $f ) )
        $to .= fgets( $f ) . ", ";
    fclose( $f );
}

$to = substr( $to, 0, -2 );

// Decode the payload
$payload = json_decode( $_POST['payload'], true );

// Just for convenience
$repository = $payload['repository'];
$commits = $payload['commits'];

// The number of commits to process
$num_commits = count( $commits );

// If there are more than one commits in the payload, add (+x more) to the subject
$more = "";
if ( $num_commits > 1 )
    $more .= " (+" . ($num_commits - 1) . " more)";

// Set the subject of the email
$subject = "[{$repository['name']}] {$commits['0']['message']}{$more}";

// Form the email content by going through all of the commits
$message = "";

foreach ( $commits as $c ) {
    $message .= "<strong>Author:</strong> {$c['author']['name']} <<a href=\"mailto:{$c['author']['email']}\">{$c['author']['email']}</a>><br />";
    $message .= "<strong>Date:</strong> {$c['timestamp']}<br />";
    $message .= "<strong>URL:</strong> {$c['url']}<br /><br />";
    $message .= "{$c['message']}<br /><br />";
    if ( $num_commits > 1 )
        $message .= "<hr /><br /><br />";
}

// Header information
$headers  = "From: {$sender}\n";
$headers .= "Reply-To: {$reply_to}\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";

// Additional parameters
$parameters = "-f{$sender}";

// Finally, send the email
mail( $to, $subject, $message, $headers, $parameters );

?>