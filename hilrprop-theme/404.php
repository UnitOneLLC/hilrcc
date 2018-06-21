<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen / HILR PROPOSAL THEME
 * @since 1.0
 * @version 1.0
 */
?>
<div class='hilr-404'>
<?php
get_header(); 
?>
</div>
<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found">
				<header class="page-header">
					<h1 class="page-title">The page you requested does not exist.</h1>
				</header><!-- .page-header -->
				<div class="page-content">
				<p>
				You’ve tried to go to a page at CCsubmit.com that doesn’t exist.
				</p>
				<p>
				Try navigating using the folder tabs above.
				</p>
				</div><!-- .page-content -->
			</section><!-- .error-404 -->
		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
