/*!
 * Helper script for Giploy
 */

/*global GiployVar:true*/

jQuery(document).ready(function($){
    // Toggle commit log
    $('.column-commit').on('click', 'code', function(){
        $(this).nextAll('pre').toggleClass('toggle');
    });
    // View remote url
    $('.column-remote').on('click', 'a', function(e){
        e.preventDefault();
        var remote = $(this).text(),
            url = $(this).attr('href');
        window.prompt(remote, url);
    });
    $('.column-dir').on('click', '.delete a', function(){
        // Delete
        return window.confirm(GiployVar.confirm);
    }).on('click', '.endpoint a', function(e){
        e.preventDefault();
        // Get Payload
        window.prompt(GiployVar.register, $(this).attr('href'));
    });
});
