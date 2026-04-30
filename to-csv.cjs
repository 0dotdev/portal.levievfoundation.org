const fs = require("fs");

const data = JSON.parse(fs.readFileSync("./output.json", "utf8"));

/**
 * Recursively flatten objects & arrays into dot notation keys
 */
function deepFlatten(obj, parentKey = "", result = {}) {
    for (let key in obj) {
        const newKey = parentKey ? `${parentKey}.${key}` : key;
        const value = obj[key];

        if (value && typeof value === "object" && !Array.isArray(value)) {
            // Normal nested object
            deepFlatten(value, newKey, result);
        } else if (Array.isArray(value)) {
            // Array → flatten each index
            value.forEach((item, idx) => {
                if (item && typeof item === "object") {
                    deepFlatten(item, `${newKey}.${idx}`, result);
                } else {
                    result[`${newKey}.${idx}`] = item;
                }
            });
        } else {
            // Primitive
            result[newKey] = value;
        }
    }
    return result;
}

// Flatten each row
const flattenedRows = data.map((row) => deepFlatten(row));

// Collect all columns across all rows
const columns = Array.from(
    new Set(flattenedRows.flatMap((row) => Object.keys(row)))
).sort();

// Build CSV data
let csv = "";

// Header row
csv += columns.join(",") + "\n";

// Data rows
for (const row of flattenedRows) {
    const line = columns
        .map((col) => {
            let val = row[col] !== undefined ? row[col] : "";

            // Escape quotes
            if (typeof val === "string") {
                val = val.replace(/"/g, '""');
            }

            return `"${val}"`;
        })
        .join(",");
    csv += line + "\n";
}

fs.writeFileSync("output-expanded.csv", csv);

console.log("✔ Fully expanded CSV → output-expanded.csv");
