<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'artesanato');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '/?2A~|O0_2yy6S~rgF:ZxgbX5<SZA#1w3aHbepL$%?tEbVZ^TIiR4 uEorvinZ5A');
define('SECURE_AUTH_KEY',  'hZ0d>fEd{H~N1-BUco ]u8Doa$KhrT65}v{0MGJZ-[2nJu434VHQrBEw`}X%A8P~');
define('LOGGED_IN_KEY',    'H>)[(O~}3dr8&Yy2wjdjxM|mPW^Xho&{Pg{V#%kd=zsM3[_vHMYEfHTMv:UPaF74');
define('NONCE_KEY',        'I-LaAC$Q{UaqvNkjp*9uNjZ70g|v.5Ui(rTB)BV$wRFrn!~Nj[c!|^$odqQmckZ/');
define('AUTH_SALT',        '%[Grmbh4*H46Mar3ver9)JF].@|$v]Ma6Pt7!Xh<T2#=OB%w4eIfMVpyL0}td;-0');
define('SECURE_AUTH_SALT', 'I8(fsmqMFv+#Kb#YqwR!r^C7Qg84EoFKXUZ7NIey8HYW<l)q27dM/JK^<.rcL=n1');
define('LOGGED_IN_SALT',   'k?`30XsI1:Khpd%h&I&yr60iJ-$?K?zKn6DfqWxnOn=}D8`jw~~eCl%~G/bE_IZ0');
define('NONCE_SALT',       'Z5x=;VE$Xfbl(M(<Fqmsuhs?;al/3K5k@.I!Sk<W<~/R aPX%H#wO_wke%9OBr|>');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
