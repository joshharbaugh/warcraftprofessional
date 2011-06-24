/* Author: 

*/

$(document).ready(function() {
	$('ul.sf-menu').superfish({
            autoArrows:  false,
            dropShadows: false
	});
	
	$(function(){
		$('#slides').slides({
			preload: true,
			preloadImage: 'img/loading.gif',
			effect: 'fade',
			crossfade: true,
			play: 10000,
			pause: 5000,
			hoverPause: true,
			generatePagination: true,
			animationStart: function(current){
				$('.caption').animate({
					bottom:-35
				},100);
			},
			animationComplete: function(current){
				$('.caption').animate({
					bottom:0
				},200);
			},
			slidesLoaded: function() {
				$('.caption').animate({
					bottom:0
				},200);
			}
		});
	});
});






















