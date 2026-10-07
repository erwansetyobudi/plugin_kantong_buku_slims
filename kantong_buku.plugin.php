<?php
/**
 * Plugin Name: Kantong Buku
 * Description: Cetak kantong buku dari data eksemplar SLiMS tanpa mengubah file inti.
 * Version: 1.1.4
 * Author: Erwan Setyo Budi
 * Author URI: https://github.com/erwansetyobudi
 */
use SLiMS\Plugins;
$plugin = Plugins::getInstance();
$plugin->registerMenu('bibliography', 'Kantong Buku', __DIR__ . '/index.php');
