const fs = require("fs");

let text = fs.readFileSync("./input.txt", "utf8");

// Remove wrapper
text = text
    .replace(/^Array\s*\(/, "")
    .replace(/\)\s*$/, "")
    .trim();

function parsePrintR(str) {
    const stack = [{}];
    let current = stack[0];
    const lines = str.split(/\r?\n/);

    for (let line of lines) {
        line = line.trim();
        if (!line) continue;

        // New nested array
        if (line.endsWith("=> Array") || line.endsWith("=> Array(")) {
            const key = line
                .split("=>")[0]
                .trim()
                .replace(/[\[\]]/g, "");
            const newObj = {};
            current[key] = newObj;
            stack.push(current);
            current = newObj;
            continue;
        }

        // End block
        if (line === ")") {
            current = stack.pop();
            continue;
        }

        // Normal key => value
        const match = line.match(/^\[(.+?)\]\s*=>\s*(.*)$/);
        if (match) {
            let key = match[1];
            let value = match[2];
            current[key] = value;
        }
    }

    return current;
}

// Parse root
let parsed = parsePrintR(text);
parsed = Object.values(parsed); // convert to array

// ---------------------------
//    FIX FIELDS THAT ARE JSON
// ---------------------------
function fixJSONFields(obj) {
    for (let key in obj) {
        let val = obj[key];

        if (typeof val !== "string") continue;

        let cleaned = val.trim();

        // Remove quotes around whole JSON
        if (
            (cleaned.startsWith('"') && cleaned.endsWith('"')) ||
            (cleaned.startsWith("'") && cleaned.endsWith("'"))
        ) {
            cleaned = cleaned.slice(1, -1);
        }

        // Detect double-escaped JSON
        if (cleaned.includes('\\"')) {
            cleaned = cleaned.replace(/\\"/g, '"');
        }

        // If it is valid JSON → parse it
        if (
            (cleaned.startsWith("{") && cleaned.endsWith("}")) ||
            (cleaned.startsWith("[") && cleaned.endsWith("]"))
        ) {
            try {
                obj[key] = JSON.parse(cleaned);
                continue;
            } catch (e) {
                // leave original if parsing fails
            }
        }

        // Convert numeric strings
        if (/^\d+$/.test(cleaned)) {
            obj[key] = Number(cleaned);
        }
    }

    return obj;
}

// Fix all rows
parsed = parsed.map(fixJSONFields);

// Save final output
fs.writeFileSync("output.json", JSON.stringify(parsed, null, 2));

console.log("✔ Conversion completed → output.json");
