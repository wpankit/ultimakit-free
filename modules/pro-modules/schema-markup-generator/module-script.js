/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

    // Add to your existing JavaScript file
    $(document).ready(function() {
        // Image selector functionality
        $('.select-image').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const input = button.prev('input');
            
            const frame = wp.media({
                title: 'Select or Upload Media',
                button: {
                    text: 'Use this media'
                },
                multiple: false
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
            });

            frame.open();
        });

        // Business hours functionality
        $('.business-hours-day input[type="checkbox"]').on('change', function() {
            const $timeInputs = $(this).closest('.business-hours-day').find('input[type="time"]');
            $timeInputs.prop('disabled', !this.checked);
        });

        // Initialize business hours checkboxes
        $('.business-hours-day input[type="checkbox"]').trigger('change');


    });

    const SchemaGenerator = {
        init: function() {
            this.bindEvents();
            this.initializeBuilders();
            this.updateSchemaPreview();
        },

        bindEvents: function() {
            $('#schema_type').on('change', this.handleSchemaTypeChange.bind(this));
            $('.add-faq-item').on('click', this.addFAQItem.bind(this));
            $(document).on('click', '.remove-faq', this.removeFAQItem.bind(this));
            $(document).on('input', '.schema-builder input, .schema-builder textarea, .schema-builder select', 
                this.debounce(this.updateSchemaPreview.bind(this), 500));
            $('.validate-schema').on('click', this.validateSchema.bind(this));
        },

        initializeBuilders: function() {
            const selectedType = $('#schema_type').val();
            if (selectedType) {
                this.showSchemaBuilder(selectedType);
            }

            // Initialize FAQ items if none exist
            if ($('.faq-item').length === 0 && selectedType === 'FAQ') {
                this.addFAQItem();
            }
        },

        // In schema-generator.js
        handleSchemaTypeChange: function(e) {
            const schemaType = $(e.target).val();
            $('.schema-builder').hide();
            if (schemaType) {
                // Update this line to use 'faqpage' instead of 'faq'
                $(`#${schemaType.toLowerCase()}-schema-builder`).show();
            }
            this.updateSchemaPreview();
        },

        showSchemaBuilder: function(type) {
            $(`#${type.toLowerCase()}-schema-builder`).show();
        },

        addFAQItem: function() {
            const template = `
                <div class="faq-item">
                    <span class="remove-faq dashicons dashicons-trash"></span>
                    <p>
                        <label>Question:</label>
                        <input type="text" class="widefat faq-question" 
                            name="faq_schema[questions][]" required>
                    </p>
                    <p>
                        <label>Answer:</label>
                        <textarea class="widefat faq-answer" 
                            name="faq_schema[answers][]" required></textarea>
                    </p>
                </div>
            `;
            $('#faq-items-container').append(template);
            this.updateSchemaPreview();
        },

        removeFAQItem: function(e) {
            if (confirm(ultimakitSchema.strings.confirmDelete)) {
                $(e.target).closest('.faq-item').remove();
                this.updateSchemaPreview();
            }
        },

        generateFAQSchema: function() {
            const faqs = [];
            $('.faq-item').each(function() {
                const question = $(this).find('.faq-question').val();
                const answer = $(this).find('.faq-answer').val();
                if (question && answer) {
                    faqs.push({
                        "@type": "Question",
                        "name": question,
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": answer
                        }
                    });
                }
            });

            return {
                "@context": "https://schema.org",
                "@type": "FAQPage",
                "mainEntity": faqs
            };
        },

        generateProductSchema: function() {
            return {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": $('#product_name').val(),
                "description": $('#product_description').val(),
                "offers": {
                    "@type": "Offer",
                    "price": $('#product_price').val(),
                    "priceCurrency": $('#product_currency').val(),
                    "availability": $('#product_availability').val()
                }
            };
        },

        generateReviewSchema: function() {
            return {
                "@context": "https://schema.org",
                "@type": "Review",
                "itemReviewed": {
                    "@type": "Thing",
                    "name": $('#review_item_name').val()
                },
                "author": {
                    "@type": "Person",
                    "name": $('#review_author').val()
                },
                "reviewRating": {
                    "@type": "Rating",
                    "ratingValue": $('#review_rating').val(),
                    "bestRating": "5"
                },
                "reviewBody": $('#review_content').val()
            };
        },

        updateSchemaPreview: function() {
            const schemaType = $('#schema_type').val();
            let schema = {};
            
            switch(schemaType) {
                case 'FAQPage':
                    schema = this.generateFAQSchema();
                    break;
                case 'Product':
                    schema = this.generateProductSchema();
                    break;
                case 'Review':
                    schema = this.generateReviewSchema();
                    break;
                case 'Article':
                    schema = this.generateArticleSchema();
                    break;
                case 'LocalBusiness':
                    schema = this.generateLocalBusinessSchema();
                    break;
                
            }

            $('#schema_data').val(JSON.stringify(schema, null, 2));
        },

        generateArticleSchema: function() {
            const schema = {
                '@context': 'https://schema.org',
                '@type': 'Article',
                'headline': $('#article_headline').val(),
                'description': $('#article_description').val(),
                'author': {
                    '@type': 'Person',
                    'name': $('#article_author').val()
                },
                'datePublished': $('#article_published_date').val(),
                'dateModified': new Date().toISOString().split('T')[0]
            };
    
            const image = $('#article_image').val();
            if (image) {
                schema.image = image;
            }
    
            const publisher = $('#article_publisher').val();
            const publisherLogo = $('#article_publisher_logo').val();
            if (publisher) {
                schema.publisher = {
                    '@type': 'Organization',
                    'name': publisher
                };
                if (publisherLogo) {
                    schema.publisher.logo = {
                        '@type': 'ImageObject',
                        'url': publisherLogo
                    };
                }
            }
    
            return schema;
        },
    
        generateLocalBusinessSchema: function() {
            const schema = {
                '@context': 'https://schema.org',
                '@type': $('#business_type').val() || 'LocalBusiness',
                'name': $('#business_name').val(),
                'description': $('#business_description').val(),
                'address': {
                    '@type': 'PostalAddress',
                    'streetAddress': $('input[name="business_schema[street]"]').val(),
                    'addressLocality': $('input[name="business_schema[city]"]').val(),
                    'addressRegion': $('input[name="business_schema[region]"]').val(),
                    'postalCode': $('input[name="business_schema[postal]"]').val(),
                    'addressCountry': $('input[name="business_schema[country]"]').val()
                }
            };
        
            // Add optional fields
            const phone = $('#business_phone').val();
            if (phone) {
                schema.telephone = phone;
            }
        
            const email = $('#business_email').val();
            if (email) {
                schema.email = email;
            }
        
            const website = $('#business_website').val();
            if (website) {
                schema.url = website;
            }
        
            // Group days with the same hours together
            const hourGroups = {};
            $('.business-hours-day').each(function() {
                const isOpen = $(this).find('input[type="checkbox"]').is(':checked');
                if (isOpen) {
                    const dayAbbr = $(this).find('label').text().trim().substring(0, 2).toUpperCase();
                    const from = $(this).find('input[name$="[from]"]').val();
                    const to = $(this).find('input[name$="[to]"]').val();
                    
                    if (from && to) {
                        const timeRange = `${from}-${to}`;
                        if (!hourGroups[timeRange]) {
                            hourGroups[timeRange] = [];
                        }
                        hourGroups[timeRange].push(dayAbbr);
                    }
                }
            });

            // Convert hour groups to opening hours strings
            const openingHours = [];
            for (const [timeRange, days] of Object.entries(hourGroups)) {
                openingHours.push(`${days.join(',')} ${timeRange}`);
            }

            if (openingHours.length > 0) {
                schema.openingHours = openingHours;
            }
        
            const priceRange = $('#business_price_range').val();
            if (priceRange) {
                schema.priceRange = priceRange;
            }
        
            return schema;
        },

        validateSchema: function() {
            const schema = $('#schema_data').val();
            const $status = $('.schema-validation-status');
        
            $.ajax({
                url: ultimakitSchema.ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_schema',
                    nonce: ultimakitSchema.nonce,
                    schema: schema
                },
                beforeSend: function() {
                    $('.schema-builder').addClass('loading');
                    $status.hide();
                },
                success: function(response) {
                    console.log('Validation response:', response); // Debug output
                    if (response.success) {
                        $status
                            .removeClass('error')
                            .addClass('success')
                            .html(ultimakitSchema.strings.validationSuccess)
                            .show();
                    } else {
                        $status
                            .removeClass('success')
                            .addClass('error')
                            .html(ultimakitSchema.strings.validationError + ': ' + response.data)
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error); // Debug output
                    $status
                        .removeClass('success')
                        .addClass('error')
                        .html('Validation request failed: ' + error)
                        .show();
                },
                complete: function() {
                    $('.schema-builder').removeClass('loading');
                }
            });
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SchemaGenerator.init();
    });

})( jQuery );