# DigitalLoca Decompiler

## Project Introduction

This project aims to decompile DigitalLoca Ply files (*.lcl) into DigitalLoca Project files (*.lcp). It's a tool specifically designed for developers and enthusiasts who need to reverse engineer or inspect the content of DigitalLoca files. To use this project, you need to have a local PHP environment installed on your machine.

## Usage

### Pre Step: Ready project
Before using php projects, execute php package installer.
```bash
composer i
```

### Step 1: Unpack LCL to LCR

To unpack an `.lcl` file into an `.lcr` file, use the following command:

```bash
php extra.php {your_lcl_file}
```

Replace `{your_lcl_file}` with the path to your `.lcl` file.

### Step 2: Restore LCR to LCP

Once you have the `.lcr` file, you can restore it to an `.lcp` file using the main script with the following command:

```bash
php main.php -t lcr -d -f {lcr_file}
```

Replace `{lcr_file}` with the path to your `.lcr` file. The `-t lcr` specifies the input type, `-d` enables debug mode (optional), and `-f` followed by the file path specifies the input file.

### Output Location

The restored project will be stored in the `R:` drive under a directory named after your `.lcr` file (e.g., if your `.lcr` file is `alpha.lcr`, the output directory will be `R:\alpha\`). If you don't have an `R:` drive, you'll need to modify the code in `src\Digital\DigiLoca.php` to reflect your preferred storage location.

## Notes

- **Extra.php**: This script is using gzuncompress(), which might be memory consuming. Be sure to raise the memory limit in your php config file(usually php.ini).
- **Exe File Format**: As for .exe format, you can search the file for the string 'DIGILOCA'. After locating this string, save all the content starting from 'DIGILOCA' as .lcr file.
- **Disk Drive Requirement**: Ensure you have an `R:` drive or modify the code to use a different drive.
- **Compatibility**: Currently, the tool is focused on decompiling older versions of `.lcl` files. The compatibility with newer versions is unknown.
- **Incomplete Parameter Types**: Some function parameter types are guessed and might be marked as `:UNK`.
- **External Resources**: If the `.lcl` file includes external resources, they might not be properly handled or included in the decompiled output.
- **Key Translation**: `OnEvent` type function keys and `GetKeyState(key)` parameters are not yet translated to `VK_xx` constants.
- **Other Potential Issues**: There may be other issues not covered here, depending on the complexity and specific content of the `.lcl` file.

## Current Limitations

1. **Limited to Old LCL Versions**: Only older versions of `.lcl` files have been studied for decompilation.
2. **Incomplete Parameter Type Guessing**: Some function parameters are not fully understood and are marked as `:UNK`.
3. **External Resource Handling**: External resources referenced in `.lcl` files are not currently supported for decompilation.
4. **Key Literal Translation**: Literal keys in `OnEvent` and `GetKeyState` functions are not translated to `VK_xx` constants.
5. **Other Potential Issues**: There might be other unforeseen issues depending on the specifics of the `.lcl` file being decompiled.

## Conclusion

This project provides a basic framework for decompiling DigitalLoca Ply files into Project files. While it has several limitations and requires a PHP environment, it can be a valuable tool for those seeking to inspect or modify DigitalLoca content. Contributions and improvements are welcome.
