# TODO for WordPress EDD Retroactive Licensing plugin

Is there something you want done? Write it up on the [support forums](http://wordpress.org/support/plugin/edd-retroactive-licensing) and then [donate](http://aihr.us/about-aihrus/donate/) or [write an awesome testimonial](http://aihr.us/about-aihrus/testimonials/add-testimonial/).

* Limit to which products to process retroactive licensing for
* Send reminders to activate licensing

* Simplify payments by item pull - this pulls a few while mine correctly grabs 372
		foreach ( $products as $product ) {
			$query    = new EDD_Payments_Query( array( 'download' => $product ) );
			$payments = $query->get_payments();

			foreach ( $payments as $payment )
				$post__in[] = $payment->ID;
		}