<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action('woocommerce_before_checkout_form', 'custom_function');
function custom_function() {
	echo "our custom content or code here";
}
class Superaddons_Checkout_Uploads_Frontend {
    function __construct(){
        add_action("wp_enqueue_scripts",array($this,"add_lib"));
        add_action("woocommerce_checkout_after_customer_details",array($this,"add_upload"));
        add_action( 'wp_ajax_superaddons_checkout_uploads', array($this,'woo_checkout_dropfiles_upload') );
        add_action( 'wp_ajax_nopriv_superaddons_checkout_uploads', array($this,'woo_checkout_dropfiles_upload') );
        add_action( 'wp_ajax_superaddons_checkout_uploads_remove', array($this,'woo_checkout_dropfiles_remove') );
        add_action( 'wp_ajax_nopriv_superaddons_checkout_uploads_remove', array($this,'woo_checkout_dropfiles_remove') );
        add_action( 'woocommerce_checkout_create_order', array($this,'save_files_checkout_field_update_order_meta'),10,2 );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array($this,'save_files_checkout_field_display_admin_order_meta'), 10, 1 );
        add_action('woocommerce_checkout_process', array($this,'upload_checkout_field_process'));
        add_filter( 'woocommerce_email_attachments', array($this,'add_pdf'), 10, 4 );
		add_action("woocommerce_order_details_after_customer_details",array($this,"save_files_checkout_field_display_admin_order_meta"));
    }
    function add_pdf( $attachments, $email_id, $order, $email ) {
        return $attachments;
    }
    function add_lib(){
		wp_enqueue_style( 'superaddons_checkout_uploads', SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_URL."assets/css/drap_drop_file_upload.css" );
		wp_enqueue_script( 'superaddons_checkout_uploads', SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_URL."assets/js/drap_drop_file_upload.js",array("jquery") );
		wp_localize_script('superaddons_checkout_uploads','superaddons_checkout_uploads',array('nonce' => wp_create_nonce('checkout_file_upload'),"url_plugin"=>SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_URL,'ajax_url' => admin_url( 'admin-ajax.php' ),"text_maximum"=>__("You can upload maximum:",'checkout-file-upload-for-woocommerce')));
    }
    function upload_checkout_field_process(){
		$upload_datas = get_option("superaddons_checkout_uploads",array("required"=>"no","label"=>"File Upload","max_size"=>"","max_files"=>"","file_type"=>"","translation1"=>"Drag & Drop Files Here","translation2"=>"or","translation3"=>"Browse Files"));
        if( isset($upload_datas["required"]) && $upload_datas["required"] == "yes") {
            if ( isset($_POST['woo_checkout_upload_files']) || $_POST['woo_checkout_upload_files'] == "" ) {
                wc_add_notice( $upload_datas["label"].' is a required field', 'error' );
            }
        }
    }
    function add_upload(){
		$upload_datas = get_option("superaddons_checkout_uploads",array("required"=>"no","label"=>"File Upload","max_size"=>"","max_files"=>"","file_type"=>"","translation1"=>"Drag & Drop Files Here","translation2"=>"or","translation3"=>"Browse Files"));
		if( isset($upload_datas["required"]) && $upload_datas["required"] == "yes" ){
            $required = "yes";
        }else{
            $required = false;
        }   
			?>
		<div class="clear"></div><!-- /.clear -->
		<h3><?php 
		echo esc_html( $upload_datas["label"] );
		if( $required == "yes" ) { echo esc_html( ' *' );} ?></h3>
		<div class="checkout-uploads-dragandrophandler-container">
			<div class="checkout-uploads-dragandrophandler" data-max="<?php echo esc_attr( $upload_datas["max_files"] ) ?>" >
				<div class="checkout-uploads-dragandrophandler-inner">
					<div class="checkout-uploads-text-drop"><?php echo esc_html( $upload_datas["translation1"]  ) ?></div>
					<div class="checkout-uploads-text-or"><?php echo esc_html( $upload_datas["translation2"]  ) ?></div>
					<div class="checkout-uploads-text-browser"><a href="#"><?php echo esc_html( $upload_datas["translation3"]  ) ?></a></div>
				</div>
				<input type="file" class="input-uploads hidden" multiple="">
			</div>
		</div><!-- /.cf7-dragandrophandler-container -->
		<input type="hidden" name="woo_checkout_upload_files" id="woo_checkout_upload_files" class="wpcf7-form-control checkout-uploads-drop-upload">
		<div class="clear"></div><!-- /.clear -->
		<?php
		wp_nonce_field( 'checkout_file_upload','checkout_file_upload_nonce');
    }
    function woo_checkout_dropfiles_remove(){
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ), 'checkout_file_upload' ) ) {
			$name = sanitize_text_field( $_POST["name"] );
			$names = explode("/",$name);
			$path = $this->get_upload_dir();
			$path_main = $path . '/'.end($names);
			if ( @is_readable( $path_main ) && @is_file( $path_main ) ) { 
				@unlink($path_main);
				wp_send_json( array("status"=>"ok" ) );
			}else{
				wp_send_json( array("status"=>"error" ) );
			}
		}
		die();
    }
    private function get_blacklist_file_ext() {
		static $blacklist = false;
		if ( ! $blacklist ) {
			$blacklist = [
				'php',
				'php3',
				'php4',
				'php5',
				'php6',
				'phps',
				'php7',
				'phtml',
				'shtml',
				'pht',
				'swf',
				'html',
				'asp',
				'aspx',
				'cmd',
				'csh',
				'bat',
				'htm',
				'hta',
				'jar',
				'exe',
				'com',
				'js',
				'lnk',
				'htaccess',
				'htpasswd',
				'phtml',
				'ps1',
				'ps2',
				'py',
				'rb',
				'tmp',
				'cgi',
				'svg',
				'php2',
				'phtm',
				'phar',
				'hphp',
				'phpt',
				'svgz',
			];
			$blacklist = apply_filters( 'woocommerce/checkout/uploads/filetypes/blacklist', $blacklist );
		}
		return $blacklist;
	}
    private function get_upload_dir() {
		$wp_upload_dir = wp_upload_dir();
		$path = $wp_upload_dir['basedir'] . '/woocommerce/checkout/uploads/';
		$path = apply_filters( 'woocommerce/checkout/uploads/upload_path', $path );
		return $path;
	}
    private function get_file_url( $file_name ) {
		$wp_upload_dir = wp_upload_dir();
		$url = $wp_upload_dir['baseurl'] . '/woocommerce/checkout/uploads/' . $file_name;
		$url = apply_filters( 'woocommerce/checkout/uploads/upload_url', $url, $file_name );
		return $url;
	}
    private function get_ensure_upload_dir() {
		$path = $this->get_upload_dir();
		if ( file_exists( $path . '/index.php' ) ) {
			return $path;
		}
		wp_mkdir_p( $path );
		$files = [
			[
				'file' => 'index.php',
				'content' => [
					'<?php',
					'// Silence is golden.',
				],
			],
			[
				'file' => '.htaccess',
				'content' => [
					'Options -Indexes',
					'<ifModule mod_headers.c>',
					'	<Files *.*>',
					'       Header set Content-Disposition attachment',
					'	</Files>',
					'</IfModule>',
				],
			],
		];
		foreach ( $files as $file ) {
			if ( ! file_exists( trailingslashit( $path ) . $file['file'] ) ) {
				$content = implode( PHP_EOL, $file['content'] );
				@ file_put_contents( trailingslashit( $path ) . $file['file'], $content );
			}
		}
		return $path;
	}
    private function is_file_type_valid( $file_types, $file ) {
		// File type validation
		if ( $file_types == "" )  {
			$file_types = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,ppt,pptx,odt,avi,ogg,m4a,mov,mp3,mp4,mpg,wav,wmv';
		}
		$file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$file_types_meta = explode( ',', $file_types );
		$file_types_meta = array_map( 'trim', $file_types_meta );
		$file_types_meta = array_map( 'strtolower', $file_types_meta );
		$file_extension = strtolower( $file_extension );
		return ( in_array( $file_extension, $file_types_meta ) && ! in_array( $file_extension, $this->get_blacklist_file_ext() ) );
	}
    private function is_file_size_valid( $file_sizes, $file ) {
		$allowed_size = ( ! empty( $file_sizes ) ) ? $file_sizes : wp_max_upload_size() / pow( 1024, 2 );
		// File size validation
		$file_size_meta = $allowed_size * pow( 1024, 2 );
		$upload_file_size = $file['size'];
		return ( $upload_file_size < $file_size_meta );
	}
    function woo_checkout_dropfiles_upload(){
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ), 'checkout_file_upload' ) ) {
			$upload_datas = get_option("superaddons_checkout_uploads",array("required"=>"no","label"=>"File Upload","max_size"=>"","max_files"=>"","file_type"=>"","translation1"=>"Drag & Drop Files Here","translation2"=>"or","translation3"=>"Browse Files"));
            $file = @$_FILES["file"];
            $size = $upload_datas["max_size"];
            $type = $upload_datas["file_type"];
            $file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$filename = uniqid() . '.' . $file_extension;
            $uploads_dir = $this->get_ensure_upload_dir();
			$filename = wp_unique_filename( $uploads_dir, $filename );
			$new_file = trailingslashit( $uploads_dir ) . $filename;
            if(!$this->is_file_type_valid($type,$file)){
				wp_send_json( array("status"=>"not","text"=>esc_html__( 'This file type is not allowed.', 'checkout-file-upload-for-woocommerce') ) );
				die();
			}
            // allowed file size?
			if ( ! $this->is_file_size_valid( $size, $file ) ) {
				wp_send_json( array("status"=>"not","text"=>esc_html__( 'This file exceeds the maximum allowed size.', 'checkout-file-upload-for-woocommerce') ) );
				die();
			}
            if ( is_dir( $uploads_dir ) && is_writable( $uploads_dir ) ) {
				$move_new_file = @ move_uploaded_file( $file['tmp_name'], $new_file );
				if ( false !== $move_new_file ) {
					// Set correct file permissions.
					$perms = 0644;
					@ chmod( $new_file, $perms );
					wp_send_json( array("status"=>"ok","text"=>$this->get_file_url( $filename ) ) );
				} else {
					wp_send_json( array("status"=>"not","text"=>esc_html__( 'There was an error while trying to upload your file.', 'checkout-file-upload-for-woocommerce') ) );
				}
			} else {
				wp_send_json( array("status"=>"not","text"=>esc_html__( 'Upload directory is not writable or does not exist.', 'checkout-file-upload-for-woocommerce') ) );
			}  
        }
    }
    function save_files_checkout_field_update_order_meta($order, $checkout_post){
		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'checkout_file_upload_nonce' ] ) ), 'checkout_file_upload' ) ) {
			if ( ! empty( $_POST['woo_checkout_upload_files'] ) ) {
				$order->update_meta_data("_woo_checkout_upload_files", sanitize_text_field($_POST["woo_checkout_upload_files"]), true);	
			}
		}
    }
    function save_files_checkout_field_display_admin_order_meta($order){
		$files= "";
		foreach ($order->get_meta_data() as $object) {
			$object_array = array_values((array)$object);
			foreach ($object_array as $object_item) {
			  if ('_woo_checkout_upload_files' == $object_item['key']) {
				$files = $object_item['value'];
				break;
			  }
			}
		  }
        if ( ! empty( $files ) ) {
            $upload_dir   = wp_upload_dir();
			$lists = explode("|",$files);
            $text = '<ul>';
            foreach ($lists as $file) {
                $text .= '<li><a href="'.$file.'" download> '.$file.' </a></li>';
            }
            $text .='</ul>';
			?>
			<p><strong><?php esc_html_e("File uploads",'checkout-file-upload-for-woocommerce') ?>: </strong>
				<?php echo wp_kses_post($text) ?>
			</p>
			<?php
        }
    }
}
new Superaddons_Checkout_Uploads_Frontend;