<?php
	// No AI Scrapers - https://github.com/jackmcconnell/no-ai-scrapers

	// Get AI scraper list, ironically by scraping it
	// cURL would probably be better here
	$dark_visitors = file_get_contents( 'https://darkvisitors.com/agents' );

	if ( !$dark_visitors ) {
		return;
	}

	// Load HTML
	$doc = new DOMDocument;
	libxml_use_internal_errors( true );
	$doc->loadHTML( $dark_visitors );
    $finder = new DOMXPath( $doc );
	$classname = "agent-type-tag";
    $list = $finder->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]" );

	// Terms we're looking for
	$terms = array( 'Scraper', 'AI Data Scraper', 'AI Search Crawler', 'AI Assistant' );

	$results = array();

	// Iterate through HTML, plucking out only desired terms
    for ( $i = $list->length - 1; $i > -1; $i-- ) {
		if ( in_array( $list->item( $i )->firstChild->nodeValue, $terms ) ) {
			$results[] = $list->item( $i )->firstChild->parentNode->parentNode->parentNode->firstElementChild->firstElementChild->nodeValue;
		}
    }

	// Change sort order A-Z
	$results = array_reverse( $results );

	// Begin building code block for adding to htaccess file
	$line_items = array(
		"\n\n" . '# BEGIN No AI Scrapers' . "\n\n"
	);

	// Rules
	foreach ( $results as $result ) {
		$line_items[] = 'User-agent: ' . $result . "\n" . 'Disallow: /' . "\n\n";
	}

	$line_items[] = '# END No AI Scrapers';


	// If the htaccess file exists, grab the content of it
	$htaccess = file_get_contents( ABSPATH . '.htaccess' );

	if ( $htaccess && strlen( $htaccess ) ) {

		// Replace rules if they already exist
		if ( str_contains( $htaccess, '# BEGIN No AI Scrapers' ) ) {
			$line_items = implode( '', $line_items );
			$regex = "/(?s)(# BEGIN No AI Scrapers).*?(# END No AI Scrapers)/";
			$htaccess = preg_replace( $regex, $line_items, $htaccess );

			// Add rules
			file_put_contents( '.htaccess', $htaccess );

			exit;
		}

		// Add rules
		file_put_contents( '.htaccess', $line_items, FILE_APPEND );
	}

?>
