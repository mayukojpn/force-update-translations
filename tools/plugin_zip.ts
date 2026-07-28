type PluginConfig = {
	slug: string;
	zip?: string;
	files: string[];
};

type DenoConfig = {
	plugin?: PluginConfig;
};

const encoder = new TextEncoder();
const config = JSON.parse(await Deno.readTextFile("deno.json")) as DenoConfig;
const plugin = config.plugin;

if (!plugin?.slug || !Array.isArray(plugin.files)) {
	throw new Error("deno.json must define plugin.slug and plugin.files.");
}

const outputFile = plugin.zip ?? `${plugin.slug}.zip`;
const tmpRoot = await Deno.makeTempDir({ prefix: `${plugin.slug}-zip-` });
const packageRoot = `${tmpRoot}/${plugin.slug}`;

async function exists(path: string): Promise<boolean> {
	try {
		await Deno.stat(path);
		return true;
	} catch (error) {
		if (error instanceof Deno.errors.NotFound) {
			return false;
		}
		throw error;
	}
}

async function copyPath(source: string, destination: string): Promise<void> {
	const stat = await Deno.stat(source);

	if (stat.isDirectory) {
		await Deno.mkdir(destination, { recursive: true });
		for await (const entry of Deno.readDir(source)) {
			await copyPath(`${source}/${entry.name}`, `${destination}/${entry.name}`);
		}
		return;
	}

	if (stat.isFile) {
		await Deno.mkdir(destination.replace(/\/[^/]+$/, ""), { recursive: true });
		await Deno.copyFile(source, destination);
	}
}

try {
	await Deno.mkdir(packageRoot, { recursive: true });

	for (const file of plugin.files) {
		if (!(await exists(file))) {
			throw new Error(`Configured plugin file does not exist: ${file}`);
		}
		await copyPath(file, `${packageRoot}/${file}`);
	}

	try {
		await Deno.remove(outputFile);
	} catch (error) {
		if (!(error instanceof Deno.errors.NotFound)) {
			throw error;
		}
	}

	const command = new Deno.Command("zip", {
		args: ["-qr", `${Deno.cwd()}/${outputFile}`, plugin.slug],
		cwd: tmpRoot,
		stderr: "piped",
		stdout: "piped",
	});
	const result = await command.output();

	if (!result.success) {
		await Deno.stderr.write(result.stderr);
		throw new Error(`zip exited with code ${result.code}`);
	}

	await Deno.stdout.write(encoder.encode(`Created ${outputFile}\n`));
} finally {
	await Deno.remove(tmpRoot, { recursive: true });
}
