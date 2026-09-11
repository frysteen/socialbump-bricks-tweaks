<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hub only tools: GitHub token, publish a release, download the plugin zip.
 *
 * Loaded by sbbt_boot() only when sbbt_is_hub() is true, so client sites
 * carry this file but never run it.
 */
class SBBT_Release {

	private static $instance = null;

	const TOKEN_OPTION  = 'sbbt_github_token';
	const LATEST_CACHE  = 'sbbt_latest_release';
	const NOTICE_PREFIX = 'sbbt_release_notice_';
	const ASSET_NAME    = 'socialbump-bricks-tweaks.zip';

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot() {
		add_action( 'sbbt_settings_after', [ $this, 'render' ] );
		add_action( 'admin_post_sbbt_save_token', [ $this, 'save_token' ] );
		add_action( 'admin_post_sbbt_publish', [ $this, 'publish' ] );
		add_action( 'admin_post_sbbt_download_zip', [ $this, 'download_zip' ] );
	}

	/* Token storage: encrypted with the site's auth salt so it never sits in the database as plain text. */

	private function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . 'sbbt-github', true );
	}

	private function encrypt( $plain ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'raw:' . base64_encode( $plain );
		}

		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plain, 'aes-256-cbc', $this->key(), OPENSSL_RAW_DATA, $iv );

		return 'enc:' . base64_encode( $iv . $cipher );
	}

	private function decrypt( $stored ) {
		$stored = (string) $stored;

		if ( strpos( $stored, 'raw:' ) === 0 ) {
			return (string) base64_decode( substr( $stored, 4 ) );
		}

		if ( strpos( $stored, 'enc:' ) !== 0 || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$data = (string) base64_decode( substr( $stored, 4 ) );

		if ( strlen( $data ) < 17 ) {
			return '';
		}

		$plain = openssl_decrypt( substr( $data, 16 ), 'aes-256-cbc', $this->key(), OPENSSL_RAW_DATA, substr( $data, 0, 16 ) );

		return $plain === false ? '' : $plain;
	}

	private function get_token() {
		return $this->decrypt( get_option( self::TOKEN_OPTION, '' ) );
	}

	/* Helpers */

	private function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( $action );
	}

	private function back( $type, $message ) {
		set_transient(
			self::NOTICE_PREFIX . get_current_user_id(),
			[
				'type'    => $type,
				'message' => $message,
			],
			5 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( admin_url( 'admin.php?page=' . SBBT_Settings::PAGE_SLUG ) . '#sbbt-release' );
		exit;
	}

	private function github( $method, $url, $token, $body = null, $headers = [] ) {
		if ( strpos( $url, 'https://' ) !== 0 ) {
			$url = 'https://api.github.com' . $url;
		}

		$args = [
			'method'  => $method,
			'timeout' => 90,
			'headers' => array_merge(
				[
					'Accept'               => 'application/vnd.github+json',
					'Authorization'        => 'Bearer ' . $token,
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'SocialBUMP-Bricks-Tweaks',
				],
				$headers
			),
		];

		if ( $body !== null ) {
			if ( is_array( $body ) ) {
				$args['body']                    = wp_json_encode( $body );
				$args['headers']['Content-Type'] = 'application/json';
			} else {
				$args['body'] = $body;
			}
		}

		$res = wp_remote_request( $url, $args );

		if ( is_wp_error( $res ) ) {
			return [
				'code'  => 0,
				'body'  => null,
				'error' => $res->get_error_message(),
			];
		}

		$code  = (int) wp_remote_retrieve_response_code( $res );
		$data  = json_decode( wp_remote_retrieve_body( $res ), true );
		$error = '';

		if ( $code >= 400 ) {
			$error = ( is_array( $data ) && ! empty( $data['message'] ) ) ? $data['message'] : 'HTTP ' . $code;
		}

		return [
			'code'  => $code,
			'body'  => $data,
			'error' => $error,
		];
	}

	private function file_version() {
		$data = get_file_data( SBBT_FILE, [ 'Version' => 'Version' ] );

		return $data['Version'];
	}

	private function bump( $src, $version ) {
		$a = 0;
		$b = 0;

		$src = preg_replace( '/^(\s*\*\s*Version:\s*)\S+/m', '${1}' . $version, $src, 1, $a );
		$src = preg_replace( "/(define\(\s*'SBBT_VERSION',\s*')[^']*(')/", '${1}' . $version . '${2}', $src, 1, $b );

		return ( $a === 1 && $b === 1 ) ? $src : null;
	}

	private function write_main_file( $contents ) {
		file_put_contents( SBBT_FILE, $contents );

		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( SBBT_FILE, true );
		}
	}

	private function lint_ok( $file ) {
		if ( ! function_exists( 'shell_exec' ) ) {
			return true;
		}

		$out = (string) shell_exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1' );

		return $out === '' || strpos( $out, 'No syntax errors' ) !== false;
	}

	/**
	 * Every plugin file, keyed by its path inside the plugin folder.
	 */
	private function files() {
		$dir   = untrailingslashit( SBBT_PATH );
		$skip  = [ '.git', '.github', 'node_modules', '.DS_Store' ];
		$list  = [];
		$items = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				function ( $file ) use ( $skip ) {
					return ! in_array( $file->getFilename(), $skip, true );
				}
			)
		);

		foreach ( $items as $file ) {
			$rel          = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) + 1 ) );
			$list[ $rel ] = $file->getPathname();
		}

		ksort( $list );

		return $list;
	}

	private function build_zip() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'sbbt_zip', __( 'This server has no ZipArchive support, so the zip could not be built.', 'sb-bricks-tweaks' ) );
		}

		$path = trailingslashit( get_temp_dir() ) . 'sbbt-' . wp_generate_password( 12, false ) . '.zip';
		$zip  = new ZipArchive();

		if ( $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
			return new WP_Error( 'sbbt_zip', __( 'The zip file could not be created.', 'sb-bricks-tweaks' ) );
		}

		foreach ( $this->files() as $rel => $abs ) {
			$zip->addFile( $abs, SBBT_SLUG . '/' . $rel );
		}

		$zip->close();

		return $path;
	}

	/**
	 * Copy the plugin files into the repo's Code tab as a single commit.
	 * README.md, LICENSE, .gitignore and .github at the top of the repo are kept.
	 * Files deleted from the plugin are removed from the repo too.
	 *
	 * @return string|WP_Error The commit SHA the release should point at.
	 */
	private function push_code( $token, $branch, $version ) {
		$git  = '/repos/' . SBBT_GITHUB_REPO . '/git';
		$fail = function ( $what, $res ) {
			/* translators: 1: step that failed, 2: GitHub error message */
			return new WP_Error( 'sbbt_git', sprintf( __( 'Copying the code to GitHub failed while %1$s (%2$s).', 'sb-bricks-tweaks' ), $what, $res['error'] ? $res['error'] : 'HTTP ' . $res['code'] ) );
		};

		$ref = $this->github( 'GET', $git . '/ref/heads/' . rawurlencode( $branch ), $token );

		if ( $ref['code'] !== 200 || empty( $ref['body']['object']['sha'] ) ) {
			return $fail( 'reading the branch', $ref );
		}

		$parent = $ref['body']['object']['sha'];
		$commit = $this->github( 'GET', $git . '/commits/' . $parent, $token );

		if ( $commit['code'] !== 200 || empty( $commit['body']['tree']['sha'] ) ) {
			return $fail( 'reading the latest commit', $commit );
		}

		$old_tree = $commit['body']['tree']['sha'];
		$files    = $this->files();
		$tree     = [];
		$keep     = [ 'README.md', 'LICENSE', '.gitignore', '.github' ];
		$top      = $this->github( 'GET', $git . '/trees/' . $old_tree, $token );

		if ( $top['code'] === 200 && ! empty( $top['body']['tree'] ) ) {
			foreach ( $top['body']['tree'] as $entry ) {
				if ( in_array( $entry['path'], $keep, true ) && ! isset( $files[ $entry['path'] ] ) ) {
					$tree[] = [
						'path' => $entry['path'],
						'mode' => $entry['mode'],
						'type' => $entry['type'],
						'sha'  => $entry['sha'],
					];
				}
			}
		}

		foreach ( $files as $rel => $abs ) {
			$content = (string) file_get_contents( $abs );
			$binary  = strpos( $content, "\0" ) !== false || ! preg_match( '//u', $content );

			if ( ! $binary ) {
				$tree[] = [
					'path'    => $rel,
					'mode'    => '100644',
					'type'    => 'blob',
					'content' => $content,
				];
				continue;
			}

			$blob = $this->github(
				'POST',
				$git . '/blobs',
				$token,
				[
					'content'  => base64_encode( $content ),
					'encoding' => 'base64',
				]
			);

			if ( $blob['code'] !== 201 || empty( $blob['body']['sha'] ) ) {
				return $fail( 'uploading ' . $rel, $blob );
			}

			$tree[] = [
				'path' => $rel,
				'mode' => '100644',
				'type' => 'blob',
				'sha'  => $blob['body']['sha'],
			];
		}

		$new_tree = $this->github( 'POST', $git . '/trees', $token, [ 'tree' => $tree ] );

		if ( $new_tree['code'] !== 201 || empty( $new_tree['body']['sha'] ) ) {
			return $fail( 'building the file list', $new_tree );
		}

		// Nothing changed since the last copy, so reuse the current commit.
		if ( $new_tree['body']['sha'] === $old_tree ) {
			return $parent;
		}

		$new_commit = $this->github(
			'POST',
			$git . '/commits',
			$token,
			[
				'message' => 'Version ' . $version,
				'tree'    => $new_tree['body']['sha'],
				'parents' => [ $parent ],
			]
		);

		if ( $new_commit['code'] !== 201 || empty( $new_commit['body']['sha'] ) ) {
			return $fail( 'creating the commit', $new_commit );
		}

		$move = $this->github(
			'PATCH',
			$git . '/refs/heads/' . rawurlencode( $branch ),
			$token,
			[
				'sha'   => $new_commit['body']['sha'],
				'force' => false,
			]
		);

		if ( $move['code'] !== 200 ) {
			return $fail( 'updating the branch', $move );
		}

		return $new_commit['body']['sha'];
	}

	private function latest_release() {
		$cached = get_transient( self::LATEST_CACHE );

		if ( $cached !== false ) {
			return $cached;
		}

		$tag = '';
		$res = wp_remote_get(
			'https://api.github.com/repos/' . SBBT_GITHUB_REPO . '/releases/latest',
			[
				'timeout' => 10,
				'headers' => [
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'SocialBUMP-Bricks-Tweaks',
				],
			]
		);

		if ( ! is_wp_error( $res ) && (int) wp_remote_retrieve_response_code( $res ) === 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $res ), true );
			$tag  = isset( $data['tag_name'] ) ? ltrim( $data['tag_name'], 'v' ) : '';
		}

		set_transient( self::LATEST_CACHE, $tag, 5 * MINUTE_IN_SECONDS );

		return $tag;
	}

	private function abort( $message, $original = null, $zip = '', $token = '', $release_id = 0 ) {
		if ( $release_id && $token ) {
			$this->github( 'DELETE', '/repos/' . SBBT_GITHUB_REPO . '/releases/' . (int) $release_id, $token );
		}

		if ( $original !== null ) {
			$this->write_main_file( $original );
		}

		if ( $zip && file_exists( $zip ) ) {
			wp_delete_file( $zip );
		}

		$this->back( 'error', $message . ' ' . __( 'Nothing was published and the version number is unchanged.', 'sb-bricks-tweaks' ) );
	}

	/* Actions */

	public function save_token() {
		$this->guard( 'sbbt_save_token' );

		if ( ! empty( $_POST['sbbt_remove_token'] ) ) {
			delete_option( self::TOKEN_OPTION );
			$this->back( 'success', __( 'GitHub token removed.', 'sb-bricks-tweaks' ) );
		}

		$token = isset( $_POST['sbbt_token'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sbbt_token'] ) ) ) : '';

		if ( $token === '' ) {
			$this->back( 'error', __( 'Paste a token into the box first. Your saved token has not changed.', 'sb-bricks-tweaks' ) );
		}

		$check = $this->github( 'GET', '/repos/' . SBBT_GITHUB_REPO, $token );

		if ( $check['code'] !== 200 ) {
			$this->back(
				'error',
				sprintf(
					/* translators: %s: GitHub error message */
					__( 'GitHub rejected that token (%s). Nothing was saved.', 'sb-bricks-tweaks' ),
					$check['error'] ? $check['error'] : 'no response'
				)
			);
		}

		update_option( self::TOKEN_OPTION, $this->encrypt( $token ), false );

		$this->back( 'success', __( 'Token saved. GitHub accepted it. Write access gets confirmed the first time you publish.', 'sb-bricks-tweaks' ) );
	}

	public function publish() {
		$this->guard( 'sbbt_publish' );

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}

		$token   = $this->get_token();
		$version = isset( $_POST['sbbt_version'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sbbt_version'] ) ) ) : '';
		$notes   = isset( $_POST['sbbt_notes'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['sbbt_notes'] ) ) ) : '';
		$current = $this->file_version();

		if ( $token === '' ) {
			$this->abort( __( 'Save a GitHub token first.', 'sb-bricks-tweaks' ) );
		}

		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			$this->abort( __( 'Use a version number in the form 1.2.0.', 'sb-bricks-tweaks' ) );
		}

		if ( version_compare( $version, $current, '<=' ) ) {
			/* translators: 1: new version, 2: current version */
			$this->abort( sprintf( __( '%1$s has to be higher than the current version, %2$s.', 'sb-bricks-tweaks' ), $version, $current ) );
		}

		$tag  = 'v' . $version;
		$repo = $this->github( 'GET', '/repos/' . SBBT_GITHUB_REPO, $token );

		if ( $repo['code'] !== 200 ) {
			/* translators: %s: GitHub error message */
			$this->abort( sprintf( __( 'Could not reach the GitHub repo (%s).', 'sb-bricks-tweaks' ), $repo['error'] ) );
		}

		$existing = $this->github( 'GET', '/repos/' . SBBT_GITHUB_REPO . '/releases/tags/' . rawurlencode( $tag ), $token );

		if ( $existing['code'] === 200 ) {
			/* translators: %s: release tag */
			$this->abort( sprintf( __( 'A release called %s is already on GitHub.', 'sb-bricks-tweaks' ), $tag ) );
		}

		// 1. Bump the version in the main plugin file, and put it back if anything fails.
		$original = file_get_contents( SBBT_FILE );
		$bumped   = $this->bump( $original, $version );

		if ( $bumped === null ) {
			$this->abort( __( 'Could not find both version lines in the main plugin file.', 'sb-bricks-tweaks' ) );
		}

		$this->write_main_file( $bumped );

		if ( ! $this->lint_ok( SBBT_FILE ) ) {
			$this->abort( __( 'The main plugin file failed a PHP syntax check after the version change.', 'sb-bricks-tweaks' ), $original );
		}

		// 2. Build the zip.
		$zip = $this->build_zip();

		if ( is_wp_error( $zip ) ) {
			$this->abort( $zip->get_error_message(), $original );
		}

		// 3. Copy the plugin files into the repo's Code tab.
		$branch     = ! empty( $repo['body']['default_branch'] ) ? $repo['body']['default_branch'] : 'main';
		$commit_sha = $this->push_code( $token, $branch, $version );

		if ( is_wp_error( $commit_sha ) ) {
			$this->abort( $commit_sha->get_error_message(), $original, $zip );
		}

		// 4. Create a draft release on that commit, attach the zip, then publish it.
		// Sites never see a release that is missing its zip.
		$release = $this->github(
			'POST',
			'/repos/' . SBBT_GITHUB_REPO . '/releases',
			$token,
			[
				'tag_name'         => $tag,
				'target_commitish' => $commit_sha,
				'name'             => $version,
				'body'             => $notes !== '' ? $notes : 'Version ' . $version,
				'draft'            => true,
			]
		);

		if ( $release['code'] !== 201 || empty( $release['body']['id'] ) ) {
			/* translators: %s: GitHub error message */
			$this->abort( sprintf( __( 'GitHub would not create the release (%s). Check the token has Contents set to Read and write.', 'sb-bricks-tweaks' ), $release['error'] ), $original, $zip );
		}

		$release_id = (int) $release['body']['id'];
		$upload_url = preg_replace( '/\{.*\}$/', '', $release['body']['upload_url'] ) . '?name=' . rawurlencode( self::ASSET_NAME );
		$upload     = $this->github( 'POST', $upload_url, $token, file_get_contents( $zip ), [ 'Content-Type' => 'application/zip' ] );

		if ( $upload['code'] !== 201 ) {
			/* translators: %s: GitHub error message */
			$this->abort( sprintf( __( 'The zip upload to GitHub failed (%s).', 'sb-bricks-tweaks' ), $upload['error'] ), $original, $zip, $token, $release_id );
		}

		$live = $this->github(
			'PATCH',
			'/repos/' . SBBT_GITHUB_REPO . '/releases/' . $release_id,
			$token,
			[
				'draft'       => false,
				'make_latest' => 'true',
			]
		);

		if ( $live['code'] !== 200 ) {
			/* translators: %s: GitHub error message */
			$this->abort( sprintf( __( 'GitHub would not publish the release (%s).', 'sb-bricks-tweaks' ), $live['error'] ), $original, $zip, $token, $release_id );
		}

		wp_delete_file( $zip );
		set_transient( self::LATEST_CACHE, $version, 5 * MINUTE_IN_SECONDS );

		$url = ! empty( $live['body']['html_url'] ) ? $live['body']['html_url'] : 'https://github.com/' . SBBT_GITHUB_REPO . '/releases';

		$this->back(
			'success',
			sprintf(
				/* translators: 1: version, 2: release URL */
				__( 'Version %1$s is live on GitHub. Other sites will pick it up the next time they check for updates. <a href="%2$s" target="_blank" rel="noopener">View the release</a>', 'sb-bricks-tweaks' ),
				esc_html( $version ),
				esc_url( $url )
			)
		);
	}

	public function download_zip() {
		$this->guard( 'sbbt_download_zip' );

		$zip = $this->build_zip();

		if ( is_wp_error( $zip ) ) {
			$this->back( 'error', $zip->get_error_message() );
		}

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . SBBT_SLUG . '-' . $this->file_version() . '.zip"' );
		header( 'Content-Length: ' . filesize( $zip ) );
		readfile( $zip );
		wp_delete_file( $zip );
		exit;
	}

	/* Screen */

	public function render() {
		$token   = $this->get_token();
		$current = $this->file_version();
		$latest  = $this->latest_release();
		$notice  = get_transient( self::NOTICE_PREFIX . get_current_user_id() );
		$parts   = array_map( 'intval', explode( '.', $current . '.0.0' ) );
		$suggest = $parts[0] . '.' . $parts[1] . '.' . ( $parts[2] + 1 );
		$repo    = 'https://github.com/' . SBBT_GITHUB_REPO;

		if ( $notice ) {
			delete_transient( self::NOTICE_PREFIX . get_current_user_id() );
		}
		?>
		<div class="sbbt-release" id="sbbt-release">
			<h2><?php esc_html_e( 'Publish release', 'sb-bricks-tweaks' ); ?></h2>
			<p class="sbbt-intro">
				<?php esc_html_e( 'This section only shows on the hub site. Publishing bumps the version, copies the plugin files into the GitHub repo and posts a release with the zip attached. Every other site then sees it as a normal plugin update, in WordPress and in MainWP.', 'sb-bricks-tweaks' ); ?>
			</p>

			<?php if ( is_array( $notice ) ) : ?>
				<div class="notice notice-<?php echo $notice['type'] === 'success' ? 'success' : 'error'; ?> inline">
					<p><?php echo wp_kses( $notice['message'], [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="sbbt-grid">
				<div class="sbbt-card sbbt-release__card">
					<h3><?php esc_html_e( 'GitHub access token', 'sb-bricks-tweaks' ); ?></h3>
					<p class="sbbt-card__desc">
						<?php esc_html_e( 'Repository:', 'sb-bricks-tweaks' ); ?>
						<a href="<?php echo esc_url( $repo ); ?>" target="_blank" rel="noopener"><?php echo esc_html( SBBT_GITHUB_REPO ); ?></a>
					</p>
					<p class="sbbt-card__desc">
						<?php
						if ( $token !== '' ) {
							/* translators: %s: last four characters of the token */
							printf( esc_html__( 'Saved token ending in %s.', 'sb-bricks-tweaks' ), '<code>' . esc_html( substr( $token, -4 ) ) . '</code>' );
						} else {
							esc_html_e( 'No token saved yet. Publishing stays switched off until there is one.', 'sb-bricks-tweaks' );
						}
						?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sbbt_save_token">
						<?php wp_nonce_field( 'sbbt_save_token' ); ?>
						<label for="sbbt_token"><?php echo $token !== '' ? esc_html__( 'Replace token', 'sb-bricks-tweaks' ) : esc_html__( 'Paste token', 'sb-bricks-tweaks' ); ?></label>
						<input type="password" id="sbbt_token" name="sbbt_token" autocomplete="off" spellcheck="false" placeholder="github_pat_...">
						<div class="sbbt-release__actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save token', 'sb-bricks-tweaks' ); ?></button>
							<?php if ( $token !== '' ) : ?>
								<button type="submit" name="sbbt_remove_token" value="1" class="button-link button-link-delete" onclick="return confirm('Remove the saved GitHub token?');"><?php esc_html_e( 'Remove token', 'sb-bricks-tweaks' ); ?></button>
							<?php endif; ?>
						</div>
					</form>
				</div>

				<div class="sbbt-card sbbt-release__card">
					<h3><?php esc_html_e( 'Publish a new version', 'sb-bricks-tweaks' ); ?></h3>
					<p class="sbbt-card__desc">
						<?php
						/* translators: 1: version on this site, 2: latest version on GitHub */
						printf( esc_html__( 'This site: %1$s. Latest on GitHub: %2$s.', 'sb-bricks-tweaks' ), '<strong>' . esc_html( $current ) . '</strong>', '<strong>' . ( $latest !== '' ? esc_html( $latest ) : esc_html__( 'none yet', 'sb-bricks-tweaks' ) ) . '</strong>' );
						?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sbbt_publish">
						<?php wp_nonce_field( 'sbbt_publish' ); ?>
						<label for="sbbt_version"><?php esc_html_e( 'New version number', 'sb-bricks-tweaks' ); ?></label>
						<input type="text" id="sbbt_version" name="sbbt_version" value="<?php echo esc_attr( $suggest ); ?>" pattern="\d+\.\d+\.\d+" required>
						<label for="sbbt_notes"><?php esc_html_e( 'What changed (optional)', 'sb-bricks-tweaks' ); ?></label>
						<textarea id="sbbt_notes" name="sbbt_notes" rows="4"></textarea>
						<div class="sbbt-release__actions">
							<button type="submit" class="button button-primary" <?php disabled( $token === '' ); ?> onclick="return confirm('Publish version ' + this.form.sbbt_version.value + ' to every site running this plugin?');"><?php esc_html_e( 'Publish release', 'sb-bricks-tweaks' ); ?></button>
						</div>
					</form>
				</div>

				<div class="sbbt-card sbbt-release__card">
					<h3><?php esc_html_e( 'Download plugin zip', 'sb-bricks-tweaks' ); ?></h3>
					<p class="sbbt-card__desc">
						<?php esc_html_e( 'The plugin exactly as it is on this site right now. Upload it to any WordPress site under Plugins, Add New, Upload Plugin.', 'sb-bricks-tweaks' ); ?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sbbt_download_zip">
						<?php wp_nonce_field( 'sbbt_download_zip' ); ?>
						<div class="sbbt-release__actions">
							<button type="submit" class="button"><?php esc_html_e( 'Download zip', 'sb-bricks-tweaks' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}