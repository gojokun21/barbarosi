<?php
/**
 * Prima pagină.
 *
 * WordPress folosește acest fișier pentru front page indiferent de setarea din
 * Setări → Citire. Secțiunile sunt în views/sections/, datele (TEMPORAR) în src/DemoContent.php.
 */

$context = Timber::context();

$context['header_transparent'] = true;
$context['home']               = DemoContent::front_page();

Timber::render( 'front-page.twig', $context );
