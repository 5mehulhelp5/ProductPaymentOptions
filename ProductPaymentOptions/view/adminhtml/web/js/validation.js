/**
 * @author      Webjump Core Team <dev@webjump.com.br>
 * @copyright   2023 Webjump (http://www.webjump.com.br)
 * @license     http://www.webjump.com.br  Copyright
 *
 * @link        http://www.webjump.com.br
 */

require([
    'jquery',
    'jquery/validate',
    'mage/translate'
], function($) {
    // $("select[name*='discount_info'][name*='default']").addClass('validate-exist-default');
    // $(document).on('click', '.action-add', function() {
    //     $("select[name*='discount_info'][name*='default']").addClass('validate-exist-default');
    // });
    //
    // $(document).on('change', "select[name*='discount_info'][name*='default']", function(element) {
    //     const fields = $("select[name*='discount_info'][name*='default']");
    //     const value = $(this).find(":selected").val();
    //     const indexElement = $(fields).index(this);
    //
    //     fields.each((index, field) => {
    //         if(value === '1' && indexElement != index) {
    //             $(field).find('option:selected').removeAttr('selected')
    //             $(field).find('option[value="0"]').attr('selected', 'selected');
    //         }
    //     });
    // });
    //
    // $('.validate-exist-default').validate();
});
