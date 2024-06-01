<?php

require ("wp-load.php");

// Vérifier si le plugin est actif
if ( is_plugin_active( 'parking-management/wp-parking-management.php' ) ) {
	echo "Le plugin est actif.";
} else {
	echo "Le plugin n'est pas actif.";
}
