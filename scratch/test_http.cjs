const http = require('http');

const paths = [
    '/inventory/recipe-management',
    '/inventory/stocks-overview',
    '/inventory/production'
];

paths.forEach(p => {
    http.get('http://127.0.0.1:8000' + p, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            console.log(p + ' -> HTTP ' + res.statusCode + ' (len: ' + data.length + ')');
            const titleMatch = data.match(/<title>(.*?)<\/title>/i);
            console.log('  Title: ' + (titleMatch ? titleMatch[1] : 'none'));
            if (res.statusCode >= 400 || data.includes('Server Error') || data.includes('Error') || data.includes('exception')) {
                const lines = data.split('\n').filter(l => l.includes('exception') || l.includes('Error') || l.includes('flare'));
                console.log('  Possible error lines:', lines.slice(0, 3));
            }
        });
    }).on('error', err => {
        console.error(p + ' -> Error: ' + err.message);
    });
});
