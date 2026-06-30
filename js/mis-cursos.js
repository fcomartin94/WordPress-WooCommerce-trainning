document.addEventListener('DOMContentLoaded', function () {
  const app = document.getElementById('mis-cursos-app');
  if (!app) return;

  fetch(CURSOS_API.url, {
    // El nonce autentica que la petición viene del usuario logueado
    headers: { 'X-WP-Nonce': CURSOS_API.nonce }
  })
    .then(function (res) {
      if (!res.ok) throw new Error('No autorizado');
      return res.json();
    })
    .then(function (pedidos) {
      if (!pedidos.length) {
        app.innerHTML = '<p>Todavía no has comprado ningún curso.</p>';
        return;
      }
      let html = '<table class="shop_table"><thead><tr>' +
        '<th>Pedido</th><th>Fecha</th><th>Estado</th><th>Total</th>' +
        '</tr></thead><tbody>';
      pedidos.forEach(function (p) {
        html += '<tr><td>#' + p.id + '</td><td>' + p.fecha +
          '</td><td>' + p.estado + '</td><td>' + p.total + '</td></tr>';
      });
      html += '</tbody></table>';
      app.innerHTML = html;
    })
    .catch(function () {
      app.innerHTML = '<p>No se pudieron cargar tus pedidos.</p>';
    });
});
