import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-es-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';
import '../css/api-docs.css';

SwaggerUIBundle({
    url: document.getElementById('swagger-ui').dataset.specUrl,
    dom_id: '#swagger-ui',
    deepLinking: true,
    filter: true,
    docExpansion: 'list',
    validatorUrl: null,
});
