jQuery(function($){
  const container = $( '#the-list' );
  const th = $('#ultimakit_order');
  let startIndex,
      stopIndex,
      order = [];

  container.sortable({
    start: function( e, ui) {
      startIndex = ui.item.index();
      startIndex = ui.item.index();
      var screenpage = jQuery('.screen-per-page').val();
      var currentpage = jQuery('.current-page').val();
      /*console.log( 'start', ui.item );*/

      container.children().not('.ui-sortable-placeholder').each(function(n,el){
       let index = $(el).find('.ultimakit_order').text();
        let index_increment = n + 1;
        let current_number = ( (currentpage - 1 )*screenpage );
        if(currentpage > 1){
          n = index_increment + current_number;
           order.push( n );
         }else{
          order.push( n + 1 );
         }
        // order.push( index );
        });

      const ph = container.find('.ui-sortable-placeholder'),
        phtd = ph.find('td'),
        tr = container.find('tr').not(ph).first();

      tr.children().each(function(i,e){
        if( $(e).css('display') === 'none' ) {
          phtd.eq(i).hide();
        }
      });

    },
    sort: function(e, ui){
    },
    stop: function(e, ui){
      stopIndex = ui.item.index();
      /*console.log( 'stop', ui.item );*/

      /*console.log( startIndex, stopIndex );*/

      if( startIndex != stopIndex ) {
        const toSave = [];

        // reorder
        const rows = container.children().not('.ui-sortable-placeholder'),
              start = Math.min( startIndex, stopIndex ),
              stop = Math.max( startIndex, stopIndex );

        for( let i = start; i <= stop; i++ ) {
          toSave.push({ id: rows.eq(i).attr('id').replace( /^[^\d]+/g, '' ), order: order[ i ] });
        }

        updatePosts( toSave );
      }
    }
  });


  function updatePosts( list ){

    const data = new FormData;

    list.forEach( (item, index) => {
      data.append( 'items[' + index + '][id]', item.id );
      data.append( 'items[' + index + '][order]', item.order );
    });

    const prom = fetch( wpApiSettings.root + 'ultimakit/v1/reorder', {
      method: 'POST', 
      cache: 'no-cache',
      headers: {
        // 'Content-Type': 'application/json',
      },
      body: data
    });

    prom.then( response => {
      if( !response.ok ) {
        /*console.log( "Failed to update item" );*/
        return;
      }

      response.json()
      .then( result => {
        if( result.status && result.saved ) {
          result.saved.forEach( item => {
            $('#post-' + item.id + ' .ultimakit_order' ).text( item.order );
          });
        }
      })
      
    });

    return prom;
  }

});
