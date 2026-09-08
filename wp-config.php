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
define( 'DB_NAME', 'wp-computer-store' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         'w@`InMI=hjb6i&@_|?J[C2fHgOC+F#WG+KGD;xzHXgjg s(v25f8&G54^RSE1(.W' );
define( 'SECURE_AUTH_KEY',  '&M=Rj?Fm!=k/].zey&^:J6R |zA`x&us5$]83hM37)6Nr[?D`~*XzTKIhB-H?XzL' );
define( 'LOGGED_IN_KEY',    'rM?3Y`$)mlMIkj90bTY][2fW65MmzRo~Ogf@*B WK}@0<1:5pn}x=i,C?O{UNZ%+' );
define( 'NONCE_KEY',        'I7Kk+`]=S4r1gwGc/mb=]dhk1r;tk`A- G#(Cn| W4,l=%URRwB[0h?B#z+>xH|=' );
define( 'AUTH_SALT',        't0*osI,VdFycLCMg@X4A=F0?4Z&>kM!qtcR&v,i=HX`^_ZZs`3YU8~=KqPYAAgU]' );
define( 'SECURE_AUTH_SALT', '!sk1u_O?(36tuO8wBPRMp6ii?qs~`|Rd.w4(JGIwIPdF%o2XNSN~zB$6imegf9~y' );
define( 'LOGGED_IN_SALT',   'Ec}QO)-P!5J4s_sQWeqaJ.*r*P+&yKH|Ny/J 6O:b~r^I ?S#RXo!z%aZ3;o c(y' );
define( 'NONCE_SALT',       ';pamza{+mx6dpA0.8jr>$PlVgawX{S|Oj7kC3v5n1DxE?4,iRD&DFh&-|$jNof *' );

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
