<?php

spl_autoload_register(function ($className) {
    static $classMap = [
        'Latte\\CompileException' => 'exceptions.php',
        'Latte\\Engine' => 'Engine.php',
        'Latte\\Helpers' => 'Helpers.php',
        'Latte\\Loader' => 'Loader.php',
        'Latte\\Macro' => 'Macro.php',

        'Latte\\Loaders\\FileLoader' => 'Loaders/FileLoader.php',
        'Latte\\Loaders\\StringLoader' => 'Loaders/StringLoader.php',
        'Latte\\Macros\\BlockMacros' => 'Macros/BlockMacros.php',
        'Latte\\Macros\\CoreMacros' => 'Macros/CoreMacros.php',
        'Latte\\Macros\\MacroSet' => 'Macros/MacroSet.php',

        'Latte\\Compiler\\Compiler' => 'Compiler/Compiler.php',
        'Latte\\Compiler\\Macro' => 'Compiler/Macro.php',
        'Latte\\Compiler\\MacroNode' => 'Compiler/MacroNode.php',
        'Latte\\Compiler\\MacroNode' => 'Compiler/MacroNode.php',
        'Latte\\Compiler\\MacroTokens' => 'Compiler/MacroTokens.php',
        'Latte\\Compiler\\Parser' => 'Compiler/Parser.php',
        'Latte\\Compiler\\Token' => 'Compiler/Token.php',
        'Latte\\Compiler\\TokenIterator' => 'Compiler/TokenIterator.php',
        'Latte\\Compiler\\Tokenizer' => 'Compiler/Tokenizer.php',
        'Latte\\Compiler\\HtmlNode' => 'Compiler/HtmlNode.php',
        'Latte\\Compiler\\PhpHelpers' => 'Compiler/PhpHelpers.php',
        'Latte\\Compiler\\PhpWriter' => 'Compiler/PhpWriter.php',
        'Latte\\Compiler\\RootNode' => 'Compiler/RootNode.php',
        'Latte\\Compiler\\Node' => 'Compiler/Node.php',

        'Latte\\RegexpException' => 'exceptions.php',
        'Latte\\Runtime\\Defaults' => 'Runtime/Defaults.php',
        'Latte\\Runtime\\HtmlStringable' => 'Runtime/HtmlStringable.php',
        'Latte\\Runtime\\CachingIterator' => 'Runtime/CachingIterator.php',
        'Latte\\Runtime\\FilterExecutor' => 'Runtime/FilterExecutor.php',
        'Latte\\Runtime\\FilterInfo' => 'Runtime/FilterInfo.php',
        'Latte\\Runtime\\Filters' => 'Runtime/Filters.php',
        'Latte\\Runtime\\Html' => 'Runtime/Html.php',
        'Latte\\Runtime\\IHtmlString' => 'Runtime/IHtmlString.php',
        'Latte\\Runtime\\ISnippetBridge' => 'Runtime/ISnippetBridge.php',
        'Latte\\Runtime\\SnippetDriver' => 'Runtime/SnippetDriver.php',
        'Latte\\Runtime\\Template' => 'Runtime/Template.php',
        'Latte\\RuntimeException' => 'exceptions.php',
        'Latte\\Strict' => 'Strict.php',
    ];

    if (isset($classMap[$className])) {
        require __DIR__ . '/Latte/' . $classMap[$className];
    }
});