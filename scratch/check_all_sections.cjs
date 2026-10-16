const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const full = path.join(dir, file);
        const stat = fs.statSync(full);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(full));
        } else if (file.endsWith('.blade.php')) {
            results.push(full);
        }
    });
    return results;
}

const files = walk('resources/views');
console.log(`Checking ${files.length} blade files for section balance...`);

files.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    const lines = content.split(/\r?\n/);
    
    // Count sections with body: @section('foo') without 2nd argument
    let sectionStarts = 0;
    let sectionEnds = 0;
    let pushStarts = 0;
    let pushEnds = 0;

    lines.forEach((l, idx) => {
        // @section('name') - 1 arg (starts a section with body)
        // vs @section('name', 'value') - 2 args (inline section)
        const secMatch = l.match(/@section\s*\(\s*['"][^'"]+['"]\s*\)/);
        if (secMatch) sectionStarts++;
        if (l.includes('@endsection')) sectionEnds++;
        if (l.match(/@push\s*\(/)) pushStarts++;
        if (l.includes('@endpush')) pushEnds++;
    });

    if (sectionStarts !== sectionEnds || pushStarts !== pushEnds) {
        console.log(`MISMATCH in ${file}:`);
        console.log(`  @section (block): ${sectionStarts}, @endsection: ${sectionEnds}`);
        console.log(`  @push: ${pushStarts}, @endpush: ${pushEnds}`);
    }
});
