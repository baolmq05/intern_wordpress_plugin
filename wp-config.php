<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'plugins' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'mysql' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '9H}mgE!soSaCuJ^^nW&c~9m]]WJ|,XO,h o&bjPNr9*D,1{dNQl&(3Kmn;Ee3@N^' );
define( 'SECURE_AUTH_KEY',  '/p|l28~= vl[_q$7,/pqV+(gN`KP35@A0$8y!jgM7/W{vH[2}qpWrWa#fYw1x(EN' );
define( 'LOGGED_IN_KEY',    '<n<J~<3elMl94JoHN]Iqy.!c7c8}PzVUDk<O5j`Ow/l.Qd&X!}FYYafgaVrS29|!' );
define( 'NONCE_KEY',        'f%2Y@(G@ ADW{Y]1a1.*:M03n|9m@{Pkkk/^ex}ZwWy* SG.EbZ0@3rel@gKf>?z' );
define( 'AUTH_SALT',        ' Qm:h#Qo2>k:Z4v5M5^2t!q~WHQoO(?,<6m}vtMd=%i^V{zUJ=(pw]7`3IZs)kYG' );
define( 'SECURE_AUTH_SALT', 'u{AL4nN_yMJB85x998Yzw#((._#uh/r^lL(Yo)p2,zT_uX#O|E;7dWtu3[KO>|.<' );
define( 'LOGGED_IN_SALT',   'T?)(E`[!=YK@Y& luz@N0KRpHmF*b?bgLm;E]vk&?qk_KTsZb(bCtq=IJ Q%mpb+' );
define( 'NONCE_SALT',       '-$6?PNMyF1-f-e63}E@}j(_AJWeE<Gm=rP1.:9n34EdSY}!E9;Z|A}],Gm =[dXY' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
