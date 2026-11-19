-- Read only. Select the correct staging database in your SQL client first.
-- Compare table/column definitions, key types and existing data before any DDL.
SELECT DATABASE() AS selected_database, VERSION() AS server_version;
SELECT table_name FROM information_schema.tables
 WHERE table_schema = DATABASE() ORDER BY table_name;
SELECT table_name, column_name, column_type, is_nullable, column_default
 FROM information_schema.columns WHERE table_schema = DATABASE()
 AND table_name IN ('users','produce_listings','produce_categories','saved_items',
 'orders','payments','subscription_plans','user_subscriptions','subscription_payments',
 'payment_events','subscription_status_history','administrative_units')
 ORDER BY table_name, ordinal_position;
SELECT table_name, index_name, non_unique, seq_in_index, column_name
 FROM information_schema.statistics WHERE table_schema = DATABASE()
 AND table_name IN ('users','produce_listings','produce_categories','saved_items','orders',
 'payments','subscription_plans','user_subscriptions','subscription_payments','administrative_units')
 ORDER BY table_name, index_name, seq_in_index;
SELECT table_name, constraint_name, column_name, referenced_table_name, referenced_column_name
 FROM information_schema.key_column_usage WHERE table_schema = DATABASE()
 AND referenced_table_name IS NOT NULL ORDER BY table_name, constraint_name, ordinal_position;
