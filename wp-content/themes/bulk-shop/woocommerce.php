<?php
get_header();
bulk_shop_header_shop_page();
get_template_part( 'template-parts/template-part', 'content' );
?>
<!-- start content container -->
<div class="row">  
	<article class="col-md-<?php bulk_main_content_width_columns(); ?>">  
        <div class="woocommerce">
			<?php woocommerce_content(); ?>
        </div>
	</article>       
	<?php get_sidebar( 'right' ); ?>
</div>
<!-- end content container -->

<?php
get_footer();
