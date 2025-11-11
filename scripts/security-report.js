#!/usr/bin/env node
/**
 * Security Analysis Report Generator
 * Runs Snyk analysis and generates prioritized security report
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

console.log('🔒 Running Security Analysis...\n');

try {
    // Run Snyk Code analysis with JSON output
    console.log('📊 Analyzing code for security vulnerabilities...');
    const codeResult = execSync('snyk code test --json', { encoding: 'utf8' });
    const codeData = JSON.parse(codeResult);
    
    // Run Snyk dependency analysis
    console.log('📦 Analyzing dependencies for vulnerabilities...');
    const depsResult = execSync('snyk test --json', { encoding: 'utf8' });
    const depsData = JSON.parse(depsResult);
    
    // Generate prioritized report
    const report = generateSecurityReport(codeData, depsData);
    
    // Save report
    const reportPath = path.join(process.cwd(), 'security-report.md');
    fs.writeFileSync(reportPath, report);
    
    console.log(`\n✅ Security report generated: ${reportPath}`);
    
} catch (error) {
    console.error('❌ Security analysis failed:', error.message);
    process.exit(1);
}

function generateSecurityReport(codeData, depsData) {
    const now = new Date().toISOString();
    
    return `# Security Analysis Report
Generated: ${now}

## Summary
- **High Priority Issues**: ${countIssuesBySeverity(codeData, 'high')}
- **Medium Priority Issues**: ${countIssuesBySeverity(codeData, 'medium')}  
- **Dependency Vulnerabilities**: ${depsData.vulnerabilities?.length || 0}

## High Priority Security Issues (Immediate Action Required)

${formatHighPriorityIssues(codeData)}

## Medium Priority Issues

${formatMediumPriorityIssues(codeData)}

## Dependency Vulnerabilities

${formatDependencyIssues(depsData)}

## Recommended Actions

### Immediate (High Priority)
1. **XSS Prevention**: Sanitize all innerHTML assignments
2. **URL Validation**: Implement proper URL validation for redirects
3. **Remove Hardcoded Secrets**: Move secrets to environment variables

### Next Steps (Medium Priority)  
1. **Input Sanitization**: Add proper input validation
2. **Content Security Policy**: Implement CSP headers
3. **Regular Security Scans**: Add to CI/CD pipeline

### Security Best Practices
- Use \`textContent\` instead of \`innerHTML\` when possible
- Validate all URLs before redirects
- Implement CSRF protection
- Use environment variables for sensitive data
`;
}

function countIssuesBySeverity(data, severity) {
    if (!data.runs?.[0]?.results) return 0;
    return data.runs[0].results.filter(r => 
        r.level === severity || r.properties?.['security-severity'] === severity
    ).length;
}

function formatHighPriorityIssues(data) {
    if (!data.runs?.[0]?.results) return 'No high priority issues found.';
    
    const highIssues = data.runs[0].results.filter(r => 
        r.level === 'error' || r.properties?.['security-severity'] === 'high'
    ).slice(0, 5); // Show top 5
    
    return highIssues.map(issue => 
        `- **${issue.ruleId}**: ${issue.message.text} (${issue.locations?.[0]?.physicalLocation?.artifactLocation?.uri})`
    ).join('\n') || 'No high priority issues found.';
}

function formatMediumPriorityIssues(data) {
    if (!data.runs?.[0]?.results) return 'No medium priority issues found.';
    
    const mediumIssues = data.runs[0].results.filter(r => 
        r.level === 'warning' || r.properties?.['security-severity'] === 'medium'
    ).slice(0, 10); // Show top 10
    
    return mediumIssues.map(issue => 
        `- **${issue.ruleId}**: ${issue.message.text} (${issue.locations?.[0]?.physicalLocation?.artifactLocation?.uri})`
    ).join('\n') || 'No medium priority issues found.';
}

function formatDependencyIssues(data) {
    if (!data.vulnerabilities || data.vulnerabilities.length === 0) {
        return '✅ No dependency vulnerabilities found.';
    }
    
    return data.vulnerabilities.slice(0, 5).map(vuln => 
        `- **${vuln.title}**: ${vuln.packageName}@${vuln.version} (${vuln.severity})`
    ).join('\n');
}