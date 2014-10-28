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
 * Please make sure that the following 7 settings are correctly set for you
 */

// Path to the file that holds the email adresses we will send the email to
$contacts_file = "contacts";

// Path to the file that holds the footer HTML we will append to the email
$footer_html_file = "footer.html";

// Path to the file that holds the header HTML we will prepend to the email
$header_html_file = "header.html";

// Path to the file that holds the trusted repository names
$trusted_repos_file = "trusted_repos";

// Character limit for the commit message displayed in the subject
$char_limit = 50;

// The reply-to email address (will be overriden by the committer's email address if payload has only one commit)
$reply_to = "";

// The sender email address
$sender = "";

/**
 * Please do not touch anything below unless you know what you're doing
 */

// Decode the payload
$payload = json_decode( $_POST['payload'], true );

// Just for convenience
$repository = $payload['repository'];
$commits = $payload['commits'];

// To store the list of trusted repositories
$trusted_repos = array();

// Open the trusted repositories file to read repository names
$f = @fopen( $trusted_repos_file, "r" );

// Read email addresses from each line to form the "to list"
if ( $f ) {
    while ( ! feof( $f ) )
        array_push( $trusted_repos, trim( fgets( $f ) ) );
    fclose( $f );
}

// Do not continue unless commits are about one of our trusted repositories
if ( ! in_array( $repository['name'], $trusted_repos ) )
    exit();

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

// The number of commits to process
$num_commits = count( $commits );

// If there are more than one commits in the payload, add (+x more) to the subject
$more = "";
if ( $num_commits > 1 )
    $more .= " (+" . ($num_commits - 1) . " more)";
else
    $reply_to = $commits['0']['author']['email'];

// Set the subject of the email
$subject = "[{$repository['name']}] " . substr( str_replace( "\n", " ", $commits['0']['message'] ), 0, $char_limit ) . "...{$more}";

// Form the email content by going through all of the commits
$message = "";

// Open the header HTML file to prepend the header to the email
$f = @fopen( $header_html_file, "r" );

// Append the header HTML
if ( $f ) {
    $message .= fread( $f, filesize( $header_html_file ) );
    fclose( $f );
}

foreach ( $commits as $c ) {
    $message .= "<strong>Author:</strong> {$c['author']['name']} <<a href=\"mailto:{$c['author']['email']}\">{$c['author']['email']}</a>><br />";
    $message .= "<strong>Date:</strong> {$c['timestamp']}<br />";
    $message .= "<strong>URL:</strong> {$c['url']}<br /><br />";
    $message .= str_replace( "\n", "<br />", $c['message'] ) . "<br /><br />";
    if ( $num_commits > 1 )
        $message .= "<hr /><br /><br />";
}

// Open the footer HTML file to append the footer to the email
$f = @fopen( $footer_html_file, "r" );

// Append the footer HTML
if ( $f ) {
    $message .= fread( $f, filesize( $footer_html_file ) );
    fclose( $f );
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
